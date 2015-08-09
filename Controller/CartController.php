<?php

namespace Ekyna\Bundle\CartBundle\Controller;

use Ekyna\Bundle\CoreBundle\Controller\Controller;
use Ekyna\Bundle\OrderBundle\Entity\OrderPayment;
use Ekyna\Bundle\OrderBundle\Event\OrderEvent;
use Ekyna\Bundle\OrderBundle\Event\OrderEvents;
use Ekyna\Bundle\OrderBundle\Event\OrderItemEvents;
use Ekyna\Bundle\PaymentBundle\Event\PaymentEvent;
use Ekyna\Bundle\PaymentBundle\Event\PaymentEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Ekyna\Bundle\OrderBundle\Event\OrderItemEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class CartController
 * @package Ekyna\Bundle\CartBundle\Controller
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class CartController extends Controller
{
    /**
     * Index action.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function indexAction(Request $request)
    {
        $cart = $this->getCart();
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
            }
            return $this->redirect($this->generateUrl('ekyna_cart_index'));
        }

        return $this->render('EkynaCartBundle:Cart:index.html.twig', array(
            'cart' => $cart,
            'form' => $form->createView()
        ));
    }

    /**
     * Informations action.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function informationsAction(Request $request)
    {
        if (null !== $redirect = $this->validateStep('information')) {
            return $redirect;
        }

        $cart = $this->getCart();

        /** @var \Ekyna\Bundle\UserBundle\Model\UserInterface $user */
        $user = $this->getUser();
        if (null !== $user) {
            $cart
                ->setUser($user)
                ->setGender($user->getGender())
                ->setFirstName($user->getFirstName())
                ->setLastName($user->getLastName())
                ->setEmail($user->getEmail())
            ;
        }

        $form = $this->createForm('ekyna_cart_step_information', $cart);

        $form->handleRequest($request);
        if ($form->isValid()) {
            $event = new OrderEvent($cart);
            $this->getDispatcher()->dispatch(OrderEvents::CONTENT_CHANGE, $event);
            if (!$event->isPropagationStopped()) {
                if ($cart->requiresShipment()) {
                    return $this->redirect($this->generateUrl('ekyna_cart_shipment'));
                }
                return $this->redirect($this->generateUrl('ekyna_cart_payment'));
            } else {
                $event->toFlashes($this->getFlashBag());
            }
            return $this->redirect($this->generateUrl('ekyna_cart_informations'));
        }

        return $this->render('EkynaCartBundle:Cart:informations.html.twig', array(
            'form' => $form->createView(),
            'cart' => $cart,
         	'user' => $user,
        ));
    }

    /**
     * Shipment action.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function shipmentAction(Request $request)
    {
        if (null !== $redirect = $this->validateStep('shipment')) {
            return $redirect;
        }

        $cart = $this->getCart();

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

            }
            return $this->redirect($this->generateUrl('ekyna_cart_shipment'));
        }*/

        return $this->render('EkynaCartBundle:Cart:shipment.html.twig', array(
//           'form' => $form->createView(),
//           'cart' => $cart,
//         	 'user' => $user,
        ));
    }

    /**
     * Payment action.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function paymentAction(Request $request)
    {
        if (null !== $redirect = $this->validateStep('payment')) {
            return $redirect;
        }

        $cart = $this->getCart();

        $payment = new OrderPayment();
        $payment->setAmount($this->get('ekyna_order.order.calculator')->calculateOrderRemainingTotal($cart));
        $cart->addPayment($payment);

        $form = $this->createForm('ekyna_cart_payment', $payment);

        $form->handleRequest($request);
        if ($form->isValid()) {
            $event = new PaymentEvent($payment);
            $this->getDispatcher()->dispatch(PaymentEvents::PREPARE, $event);
            if (null !== $response = $event->getResponse()) {
                return $response;
            }
        }

        return $this->render('EkynaCartBundle:Cart:payment.html.twig', array(
            'cart' => $cart,
            'form' => $form->createView(),
        ));
    }

    /**
     * Confirmation action.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function confirmationAction(Request $request)
    {
        $order = $this->get('ekyna_order.order.repository')->findOneByKey(
            $request->attributes->get('key')
        );

        if (null === $order) {
            throw new NotFoundHttpException('Order not found.');
        }

        if (null !== $user = $order->getUser()) {
            if ($user !== $this->getUser()) {
                throw new AccessDeniedHttpException('You are not allowed to view this resource.');
            }
        }

        return $this->render('EkynaCartBundle:Cart:confirmation.html.twig', array('order' => $order));
    }

    /**
     * Reset action.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function resetAction(Request $request)
    {
        $cart = $this->getCart();
        $event = new OrderEvent($cart);
        $this->get('ekyna_order.order.operator')->delete($event, true);
        if (!$event->hasErrors()) {
            $this->addFlash('ekyna_cart.message.reset');
        } else {
            $event->toFlashes($this->getFlashBag());
        }

        return $this->redirectAfterContentChange($request);
    }

    /**
     * Remove item action.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function removeItemAction(Request $request)
    {
        $cart = $this->getCart();
        $item = $this->getDoctrine()
            ->getRepository('EkynaOrderBundle:OrderItem')
            ->findOneBy(array(
                'id' => $request->attributes->get('itemId'),
                'orderId' => $cart->getId(),
            ));

        if (null === $item) {
            throw new NotFoundHttpException($this->getTranslator()->trans('ekyna_cart.message.item_not_found'));
        }

        $event = new OrderItemEvent($cart, $item);
        $this->getDispatcher()->dispatch(OrderItemEvents::REMOVE, $event);
        if (!$event->isPropagationStopped()) {
            $message = 'ekyna_cart.message.item_remove.success';
            $type    = 'success';
        } else {
            $message = 'ekyna_cart.message.item_remove.failure';
            $type    = 'danger';
        }
        $this->addFlash($this->getTranslator()->trans($message, array(
            '{{ name }}' => $item->getDesignation(),
            '{{ path }}' => $this->generateUrl('ekyna_cart_index'),
        )), $type);

        if ($request->isXmlHttpRequest()) {
            // TODO
            return new Response();
        }

        return $this->redirectAfterContentChange($request);
    }

    /**
     * Validates the cart for the given step name.
     *
     * @param string $step
     * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function validateStep($step)
    {
        $stepGroups = array(
            'information' => array('ekyna_cart_index',        array('Default', 'Cart')),
            'shipment'    => array('ekyna_cart_informations', array('Default', 'Cart', 'Information')),
            'payment'     => array('ekyna_cart_shipment',     array('Default', 'Cart', 'Information', 'Shipment')),
        );
        if (!array_key_exists($step, $stepGroups)) {
            throw new \InvalidArgumentException(sprintf('Undefined step "%s".', $step));
        }

        $cart = $this->getCart();
        $errorList = $this->get('validator')->validate($cart, $stepGroups[$step][1]);
        if (0 != $errorList->count()) {
            return $this->redirect($this->generateUrl($stepGroups[$step][0]));
        }

        return null;
    }

    /**
     * Check that user is logged.
     *
     * @param Request $request
     * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function securityCheck(Request $request)
    {
        if (!$this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $request->getSession()->set('_ekyna.login_success.target_path', 'ekyna_cart_informations');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        return null;
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

    /**
     * Returns the cart.
     *
     * @return \Ekyna\Component\Sale\Order\OrderInterface
     */
    protected function getCart()
    {
        return $this->get('ekyna_cart.cart.provider')->getCart();
    }
}
