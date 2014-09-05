<?php

namespace Ekyna\Bundle\CartBundle\Form\Type;

use Ekyna\Bundle\AdminBundle\Form\Type\ResourceFormType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * CartType
 *
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class CartType extends ResourceFormType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('items', 'collection', array(
                'type' => 'ekyna_cart_item',
                'label' => false
            ))
            ->add('save', 'submit', array(
                'label' => 'Appliquer'
            ))
            ->add('saveAndContinue', 'submit', array(
                'label' => 'Valider mon panier'
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
    	return 'ekyna_cart';
    }
}
