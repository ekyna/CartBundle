<?php

namespace Ekyna\Bundle\CartBundle\Controller;

use Ekyna\Bundle\CoreBundle\Exception\RedirectException;
use Ekyna\Bundle\OrderBundle\Entity\OrderPayment;
use Ekyna\Bundle\OrderBundle\Event\OrderEvent;
use Ekyna\Bundle\OrderBundle\Event\OrderEvents;
use Ekyna\Bundle\PaymentBundle\Payum\Request\PaymentStatusRequest;
use Ekyna\Component\Sale\Payment\PaymentStates;
use Ekyna\Component\Sale\Order\OrderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CartController.
 *
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class CartController extends Controller
{
    public function indexAction(Request $request)
    {
        $cart = $this->get('ekyna_cart.cart_provider')->getCart();

        $form = $this->createForm('ekyna_cart', $cart);

        $form->handleRequest($request);
        if ($form->isValid()) {
            $this
                ->ensureCartIsNotLocked($cart)
                ->ensureCartIsNotEmpty($cart)
            ;
            $this->get('event_dispatcher')->dispatch(OrderEvents::POST_CONTENT_CHANGE, new OrderEvent($cart));

            if ($request->isXmlHttpRequest()) {
                // TODO
                return new Response();
            }

            if($form->get('saveAndContinue')->isClicked()) {
                return $this->redirect($this->generateUrl('ekyna_cart_informations'));
            }

            $this->get('session')->getFlashBag()->add('success', 'Votre panier a bien été modifié.');
        }

        return $this->render(
            'EkynaCartBundle:Cart:index.html.twig',
            array(
                'cart' => $cart,
                'form' => $form->createView()
            )
        );
    }

    public function informationsAction(Request $request)
    {
        $cart = $this->get('ekyna_cart.cart_provider')->getCart();
        $this
            ->ensureCartIsNotLocked($cart)
            ->ensureCartIsNotEmpty($cart)
        ;

        $user = $this->getUser();

        if (! $this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $request->getSession()->set('_ekyna.login_success.target_path', 'ekyna_cart_informations');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }

        $cart->setUser($user);

        $form = $this->createForm('ekyna_cart_addresses', $cart, array(
        	'user' => $user
        ));

        $eventDispatcher = $this->get('event_dispatcher');
        $eventDispatcher->dispatch(OrderEvents::PRE_CONTENT_CHANGE, new OrderEvent($cart));

        $form->handleRequest($request);
        if ($form->isValid()) {
            $eventDispatcher->dispatch(OrderEvents::POST_CONTENT_CHANGE, new OrderEvent($cart));

            if ($cart->requiresShipment()) {
                return $this->redirect($this->generateUrl('ekyna_cart_shipping'));
            } else {
                return $this->redirect($this->generateUrl('ekyna_cart_payment'));
            }
        }

        return $this->render(
            'EkynaCartBundle:Cart:informations.html.twig',
            array(
                'form' => $form->createView(),
                'cart' => $cart,
        	    'user' => $user,
            )
        );
    }

    public function shippingAction(Request $request)
    {
        if (! $this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }

        $cart = $this->get('ekyna_cart.cart_provider')->getCart();
        $this
            ->ensureCartIsNotLocked($cart)
            ->ensureCartIsNotEmpty($cart)
        ;

        // Go to payment page if no shipment required
        if (! $cart->requiresShipment()) {
            return $this->redirect($this->generateUrl('ekyna_cart_payment'));
        }

        /*
        $form = $this->createForm('ekyna_cart_shipment', $cart);

        $eventDispatcher = $this->get('event_dispatcher');
        $eventDispatcher->dispatch(OrderEvents::PRE_CONTENT_CHANGE, new OrderEvent($cart));

        $form->handleRequest($request);
        if ($form->isValid()) {
            $eventDispatcher->dispatch(OrderEvents::POST_CONTENT_CHANGE, new OrderEvent($cart));

            return $this->redirect($this->generateUrl('ekyna_cart_payment'));
        }*/

        return $this->render('EkynaCartBundle:Cart:shipping.html.twig');
    }

    public function paymentAction(Request $request)
    {
        if (! $this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }

        $cart = $this->get('ekyna_cart.cart_provider')->getCart();
        $this
            ->ensureCartIsNotLocked($cart)
            ->ensureCartIsNotEmpty($cart)
        ;

        if(null !== $method = $request->query->get('method', null)) {

            $eventDispatcher = $this->get('event_dispatcher');
            $eventDispatcher->dispatch(OrderEvents::PRE_PAYMENT_PROCESS, new OrderEvent($cart));
            $eventDispatcher->dispatch(OrderEvents::PRE_CONTENT_CHANGE, new OrderEvent($cart));

            $payment = new OrderPayment();
            $payment
                ->setAmount($cart->getAtiTotal())
                ->setCurrency('EUR')
                ->setMethod($method)
            ;

            $cart->addPayment($payment);

            $eventDispatcher->dispatch(OrderEvents::POST_CONTENT_CHANGE, new OrderEvent($cart));

            $captureToken = $this->get('payum.security.token_factory')->createCaptureToken(
                $method,
                $payment,
                'ekyna_cart_payment_check' // the route to redirect after capture;
            );

            return $this->redirect($captureToken->getTargetUrl());
        }

        return $this->render('EkynaCartBundle:Cart:payment.html.twig');
    }

    public function paymentCheckAction(Request $request)
    {
        if (! $this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }

        $httpRequestVerifier = $this->get('payum.security.http_request_verifier');
        $token = $httpRequestVerifier->verify($request);

        $status = new PaymentStatusRequest($token);
        $this->get('payum')->getPayment($token->getPaymentName())->execute($status);

        $payment = $status->getModel();
        $cart = $payment->getOrder();

        $httpRequestVerifier->invalidate($token);

        $success = false;
        if (in_array($payment->getState(), array(PaymentStates::STATE_SUCCESS, PaymentStates::STATE_COMPLETED))) {
            $this->get('session')->getFlashBag()->set('success', 'ekyna_payment.success.message');
            $success = true;
        } else if ($payment->getState() == PaymentStates::STATE_PENDING) {
            $this->get('session')->getFlashBag()->set('warning', 'ekyna_payment.pending.message');
            $success = true;
        } else {
            $this->get('session')->getFlashBag()->set('danger', 'ekyna_payment.failed.message');
        }

        $this->get('event_dispatcher')->dispatch(OrderEvents::POST_PAYMENT_PROCESS, new OrderEvent($cart));

        if (!$success) {
            return $this->redirect($this->generateUrl('ekyna_cart_payment'));
        }

        return $this->redirect($this->generateUrl('ekyna_cart_confirmation'));
    }

    public function confirmationAction(Request $request)
    {
        if (! $this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }

        return $this->render(
            'EkynaCartBundle:Cart:confirmation.html.twig'
        );
    }

    public function addItemAction(Request $request)
    {
        $messageType = 'info';
        $message = '';

        $cart = $this->get('ekyna_cart.cart_provider')->getCart();

        $this->ensureCartIsNotLocked($cart);

        $item = $this->get('ekyna_cart.cart_item.factory')->createItemFromRequest($request);

        $eventDispatcher = $this->get('event_dispatcher');
        $eventDispatcher->dispatch(OrderEvents::PRE_CONTENT_CHANGE, new OrderEvent($cart));

        $cart->addItem($item);

        $eventDispatcher->dispatch(OrderEvents::POST_CONTENT_CHANGE, new OrderEvent($cart));

        $message = sprintf(
            'L\'article "%s" a bien été ajouté à <a href="%s">votre panier</a>.', 
            $item->getProduct()->getDesignation(),
            $this->generateUrl('ekyna_cart_index')
        );

        if ($request->isXmlHttpRequest()) {
            // TODO
            return new Response();
        }

        $this->get('session')->getFlashBag()->add($messageType, $message);

        if(null !== $referer = $request->headers->get('referer', null)) {
            return $this->redirect($referer);
        }

        return $this->redirect($this->generateUrl('ekyna_cart_index'));
    }

    public function removeItemAction(Request $request)
    {
        $cart = $this->get('ekyna_cart.cart_provider')->getCart();

        $this
            ->ensureCartIsNotLocked($cart)
            ->ensureCartIsNotEmpty($cart)
        ;

        $item = $this->getDoctrine()->getRepository('EkynaOrderBundle:OrderItem')->find($request->attributes->get('itemId'));
        if (null === $item || !$cart->hasItem($item)) {
            $messageType = 'danger';
            $message = 'Article introuvable.';
        } else {
            $eventDispatcher = $this->get('event_dispatcher');
            $eventDispatcher->dispatch(OrderEvents::PRE_CONTENT_CHANGE, new OrderEvent($cart));

            $cart->removeItem($item);
            $em = $this->getDoctrine()->getManager();
            $em->remove($item);
            $em->flush();

            $eventDispatcher->dispatch(OrderEvents::POST_CONTENT_CHANGE, new OrderEvent($cart));

            $message = sprintf(
                'L\'article "%s" a bien été supprimé de <a href="%s">votre panier</a>.',
                $item->getProduct()->getDesignation(),
                $this->generateUrl('ekyna_cart_index')
            );
        }

        if ($request->isXmlHttpRequest()) {
            // TODO
            return new Response();
        }

        $this->get('session')->getFlashBag()->add($messageType, $message);

        if(null !== $referer = $request->headers->get('referer', null)) {
            return $this->redirect($referer);
        }

        return $this->redirect($this->generateUrl('ekyna_cart_index'));
    }

    private function ensureCartIsNotLocked(OrderInterface $cart)
    {
        if ($cart->getLocked()) {
            $exception = new RedirectException('Votre panier est vérouillé pour paiement et ne peut être modifié.');
            $exception->setUri($this->generateUrl('ekyna_cart_index'));
            $exception->setMessageType('warning');
            throw $exception;
        }

        return $this;
    }

    private function ensureCartIsNotEmpty(OrderInterface $cart)
    {
        if ($cart->isEmpty()) {
            $exception = new RedirectException('Votre panier est vide.');
            $exception->setUri($this->generateUrl('ekyna_cart_index'));
            $exception->setMessageType('warning');
            throw $exception;
        }

        return $this;
    }
}
