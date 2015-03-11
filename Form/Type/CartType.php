<?php

namespace Ekyna\Bundle\CartBundle\Form\Type;

use Ekyna\Bundle\AdminBundle\Form\Type\ResourceFormType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class CartType
 * @package Ekyna\Bundle\CartBundle\Form\Type
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
            ->add('items', 'ekyna_core_collection', array(
                'type' => 'ekyna_cart_item',
                'label' => false,
                'allow_add'    => true,
                'allow_delete' => true,
                'allow_sort'   => false, // TODO ?
                'by_reference' => false,
                'add_button_text' => 'ekyna_core.button.add',
                'sub_widget_col'  => 11,
                'button_col'      => 1,
                'attr' => array(
                    'widget_col' => 12
                ),
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
