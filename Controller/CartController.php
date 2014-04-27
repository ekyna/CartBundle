<?php

namespace Ekyna\Bundle\CartBundle\Controller;

use Ekyna\Bundle\CartBundle\Events\CartEvents;
use Ekyna\Bundle\CartBundle\Events\CartEvent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
        $eventDispatcher = $this->get('event_dispatcher');

        $form = $this->createForm('ekyna_cart', $cart);

        $form->handleRequest($request);
        if ($form->isValid()) {

            $eventDispatcher->dispatch(CartEvents::UPDATED, new CartEvent($cart));

            $em = $this->getDoctrine()->getManager();
            $em->persist($cart);
            $em->flush();

            $eventDispatcher->dispatch(CartEvents::SAVED, new CartEvent($cart));

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
        $user = $this->getUser();

        if ($cart->isEmpty()) {
            return $this->redirect($this->generateUrl('ekyna_cart_index'));
        }

        if (! $this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $request->getSession()->set('_ekyna.login_success.target_path', 'ekyna_cart_informations');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }

        $cart->setUser($user);
        
        $form = $this->createForm('ekyna_cart_addresses', $cart, array(
        	'user' => $user
        ));

        $form->handleRequest($request);
        if ($form->isValid()) {

            //$eventDispatcher->dispatch(CartEvents::UPDATED, new CartEvent($cart));

            $em = $this->getDoctrine()->getManager();
            $em->persist($cart);
            $em->flush();

            //$eventDispatcher->dispatch(CartEvents::SAVED, new CartEvent($cart));

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

        // Go to payment page if no shipment required
        if (! $cart->requiresShipment()) {
            return $this->redirect($this->generateUrl('ekyna_cart_payment'));
        }

        return $this->render('EkynaCartBundle:Cart:shipping.html.twig');
    }

    public function paymentAction(Request $request)
    {
        if (! $this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }

        return $this->render('EkynaCartBundle:Cart:payment.html.twig');
    }

    public function confirmationAction(Request $request)
    {
        if (! $this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }

        return $this->render('EkynaCartBundle:Cart:confirmation.html.twig');
    }

    public function addItemAction(Request $request)
    {
        $item = $this->get('ekyna_cart.cart_item.factory')->createItemFromRequest($request);

        $cartProvider = $this->get('ekyna_cart.cart_provider');
        $eventDispatcher = $this->get('event_dispatcher');

        $cart = $cartProvider->getCart();
        $cart->addItem($item);

        $eventDispatcher->dispatch(CartEvents::UPDATED, new CartEvent($cart));

        $em = $this->getDoctrine()->getManager();
        $em->persist($cart);
        $em->flush();

        $eventDispatcher->dispatch(CartEvents::SAVED, new CartEvent($cart));

        if ($request->isXmlHttpRequest()) {
            // TODO
            return new Response();
        }

        $this->get('session')->getFlashBag()->add('info', sprintf(
            'L\'article "%s" a bien été ajouté à <a href="%s">votre panier</a>.', 
            $item->getProduct()->getDesignation(),
            $this->generateUrl('ekyna_cart_index')
        ));

        if(null !== $referer = $request->headers->get('referer', null)) {
            return new RedirectResponse($referer);
        }

        return new RedirectResponse($this->generateUrl('ekyna_cart_index'));
    }

    public function removeItemAction(Request $request)
    {
        $cartProvider = $this->get('ekyna_cart.cart_provider');
        $eventDispatcher = $this->get('event_dispatcher');

        $cart = $cartProvider->getCart();

        $eventDispatcher->dispatch(CartEvents::UPDATED, new CartEvent($cart));

        $em = $this->getDoctrine()->getManager();
        $em->persist($cart);
        $em->flush();

        $eventDispatcher->dispatch(CartEvents::SAVED, new CartEvent($cart));
    }
}
