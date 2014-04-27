<?php

namespace Ekyna\Bundle\CartBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

/**
 * CartAddressesType.
 *
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class CartAddressesType extends AbstractType
{
    /**
     * @var string
     */
    protected $dataClass;

    /**
     * Constructor.
     * 
     * @param string $dataClass
     */
    public function __construct($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $queryBuilder = function(EntityRepository $er) use ($options) {
            $qb = $er->createQueryBuilder('a');
            $qb
                ->andWhere($qb->expr()->eq('a.user', ':user'))
                ->andWhere($qb->expr()->eq('a.locked', ':locked'))
                ->setParameter('user', $options['user'])
                ->setParameter('locked', false)
            ;
            return $qb;
        };

        $builder
            ->add('invoiceAddress', 'ekyna_cart_address_choice', array(
                'label' => 'Adresse de facturation',
                'query_builder' => $queryBuilder,
            ))
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) use ($queryBuilder) {
            $cart = $event->getData();
            $form = $event->getForm();

            if (!$cart || null === $cart->getId() || $cart->isEmpty()) {
                throw new \RuntimeException('CartAddressesType can\'t be used with a non persisted or empty cart.');
            }

            if ($cart->requiresShipment()) {
                $form
                    ->add('deliveryAddress', 'ekyna_cart_address_choice', array(
                        'label' => 'Adresse de livraison',
                        'query_builder' => $queryBuilder,
                    ))
                ;
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults(array(
                'data_class'    => $this->dataClass,
                'user'          => null,
            ))
            ->setRequired(array('data_class', 'user'))
            ->setAllowedTypes(array(
            	'data_class'    => 'string',
            	'user'          => 'Ekyna\Bundle\UserBundle\Model\UserInterface',
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
    	return 'ekyna_cart_addresses';
    }
}
