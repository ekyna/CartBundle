<?php

namespace Ekyna\Bundle\CartBundle\Controller;

use Ekyna\Bundle\CoreBundle\Controller\Controller;
use Ekyna\Bundle\OrderBundle\Entity\OrderPayment;
use Ekyna\Bundle\OrderBundle\Event\OrderEvent;
use Ekyna\Bundle\OrderBundle\Event\OrderEvents;
use Ekyna\Bundle\PaymentBundle\Payum\Request\PaymentStatusRequest;
use Ekyna\Component\Sale\Payment\PaymentStates;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Ekyna\Bundle\OrderBundle\Event\OrderItemEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class CartController
 * @package Ekyna\Bundle\CartBundle\Controller
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
            $event = new OrderEvent($cart);
            $this->getDispatcher()->dispatch(OrderEvents::CONTENT_CHANGE, $event);
            if (!$event->isPropagationStopped()) {
                /** @var \Symfony\Component\Form\SubmitButton $button */
                $button = $form->get('saveAndContinue');
                if ($button->isClicked()) {
                    return $this->redirect($this->generateUrl('ekyna_cart_informations'));
                }
            } else {
                $event->toFlashes($this->getFlashBag());
                //return $this->redirect($this->generateUrl('ekyna_cart_index'));
            }
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
        if (!$this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $request->getSession()->set('_ekyna.login_success.target_path', 'ekyna_cart_informations');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }

        $cart = $this->get('ekyna_cart.cart_provider')->getCart();
        $user = $this->getUser();
        $cart->setUser($user);

        $form = $this->createForm('ekyna_cart_addresses', $cart, array(
            'user' => $user
        ));

        $form->handleRequest($request);
        if ($form->isValid()) {
            $event = new OrderEvent($cart);
            $this->getDispatcher()->dispatch(OrderEvents::CONTENT_CHANGE, $event);
            if (!$event->isPropagationStopped()) {
                if ($cart->requiresShipment()) {
                    return $this->redirect($this->generateUrl('ekyna_cart_shipping'));
                }
                return $this->redirect($this->generateUrl('ekyna_cart_payment'));
            } else {
                $event->toFlashes($this->getFlashBag());
                //return $this->redirect($this->generateUrl('ekyna_cart_informations'));
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

    public function shippingAction()
    {
        if (!$this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }

        $cart = $this->get('ekyna_cart.cart_provider')->getCart();

        // Go to payment page if no shipment required
        if (!$cart->requiresShipment()) {
            return $this->redirect($this->generateUrl('ekyna_cart_payment'));
        }

        /*
        $form = $this->createForm('ekyna_cart_shipment', $cart);

        $form->handleRequest($request);
        if ($form->isValid()) {
            $event = new OrderEvent($cart);
            $this->getDispatcher()->dispatch(OrderEvents::CONTENT_CHANGE, $event);
            if (!$event->isPropagationStopped()) {
                return $this->redirect($this->generateUrl('ekyna_cart_payment'));
            } else {
                $this->displayResourceEventMessages($event);
                //return $this->redirect($this->generateUrl('ekyna_cart_shipping'));
            }
        }*/

        return $this->render(
            'EkynaCartBundle:Cart:shipping.html.twig',
            array(
//                 'form' => $form->createView(),
//                 'cart' => $cart,
//         	    'user' => $user,
            )
        );
    }

    public function paymentAction(Request $request)
    {
        if (!$this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }

        $cart = $this->get('ekyna_cart.cart_provider')->getCart();

        if (null !== $method = $request->query->get('method', null)) {
            $payment = new OrderPayment();
            $payment
                ->setAmount($cart->getAtiTotal())
                ->setCurrency('EUR')
                ->setMethod($method);
            $cart->addPayment($payment);

            $event = new OrderEvent($cart);
            $this->getDispatcher()->dispatch(OrderEvents::PAYMENT_INITIALIZE, $event);
            if (!$event->isPropagationStopped()) {
                $captureToken = $this->get('payum.security.token_factory')->createCaptureToken(
                    $method,
                    $payment,
                    'ekyna_cart_payment_check' // the route to redirect after capture;
                );
                return $this->redirect($captureToken->getTargetUrl());
            } else {
                $event->toFlashes($this->getFlashBag());
                //return $this->redirect($this->generateUrl('ekyna_cart_payment'));
            }
        }

        return $this->render('EkynaCartBundle:Cart:payment.html.twig');
    }

    public function paymentCheckAction(Request $request)
    {
        if (!$this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
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
            $this->addFlash('ekyna_payment.success.message', 'success');
            $success = true;
        } else if ($payment->getState() == PaymentStates::STATE_PENDING) {
            $this->addFlash('ekyna_payment.pending.message', 'warning');
            $success = true;
        } else {
            $this->addFlash('ekyna_payment.failed.message', 'danger');
        }

        $event = new OrderEvent($cart);
        $this->getDispatcher()->dispatch(OrderEvents::PAYMENT_COMPLETE, $event);
        if ($event->isPropagationStopped()) {
            $event->toFlashes($this->getFlashBag());
        }

        if (!$success) {
            return $this->redirect($this->generateUrl('ekyna_cart_payment'));
        }

        return $this->redirect($this->generateUrl('ekyna_cart_confirmation'));
    }

    public function confirmationAction()
    {
        if (!$this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }

        return $this->render(
            'EkynaCartBundle:Cart:confirmation.html.twig'
        );
    }

    public function resetAction(Request $request)
    {
        $cart = $this->get('ekyna_cart.cart_provider')->getCart();

        $event = new OrderEvent($cart);
        $this->getDispatcher()->dispatch(OrderEvents::DELETE, $event);
        // Event propagation is stopped without errors.
        if (!$event->hasErrors()) {
            $this->addFlash('ekyna_cart.event.reset');
        } else {
            $event->toFlashes($this->getFlashBag());
        }

        return $this->redirectAfterContentChange($request);
    }

    public function addItemAction(Request $request)
    {
        $cart = $this->get('ekyna_cart.cart_provider')->getCart();
        $item = $this->get('ekyna_order.order_item.factory')->createItemFromRequest($request);

        if (null === $item) {
            throw new NotFoundHttpException($this->getTranslator()->trans('ekyna_cart.event.item_not_found'));
        }

        $event = new OrderItemEvent($cart, $item);
        $this->getDispatcher()->dispatch(OrderEvents::ITEM_ADD, $event);
        if (!$event->isPropagationStopped()) {
            $this->addFlash($this->getTranslator()->trans('ekyna_cart.event.item_add', array(
                '{{ name }}' => $item->getProduct()->getDesignation(),
                '{{ path }}' => $this->generateUrl('ekyna_cart_index'),
            )));
        } else {
            $event->toFlashes($this->getFlashBag());
        }

        if ($request->isXmlHttpRequest()) {
            // TODO
            return new Response();
        }

        return $this->redirectAfterContentChange($request);
    }

    public function removeItemAction(Request $request)
    {
        $cart = $this->get('ekyna_cart.cart_provider')->getCart();
        $item = $this->getDoctrine()
            ->getRepository('EkynaOrderBundle:OrderItem')
            ->find($request->attributes->get('itemId'));

        if (null === $item) {
            throw new NotFoundHttpException($this->getTranslator()->trans('ekyna_cart.event.item_not_found'));
        }

        $event = new OrderItemEvent($cart, $item);
        $this->getDispatcher()->dispatch(OrderEvents::ITEM_ADD, $event);
        if (!$event->isPropagationStopped()) {
            $this->addFlash($this->getTranslator()->trans('ekyna_cart.event.item_remove', array(
                '{{ name }}' => $item->getProduct()->getDesignation(),
                '{{ path }}' => $this->generateUrl('ekyna_cart_index'),
            )));
        } else {
            $event->toFlashes($this->getFlashBag());
        }

        if ($request->isXmlHttpRequest()) {
            // TODO
            return new Response();
        }

        return $this->redirectAfterContentChange($request);
    }

    /**
     * Redirects after order content changed.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function redirectAfterContentChange(Request $request)
    {
        if (null !== $referer = $request->headers->get('referer', null)) {
            return $this->redirect($referer);
        }

        return $this->redirect($this->generateUrl('ekyna_cart_index'));
    }
}
