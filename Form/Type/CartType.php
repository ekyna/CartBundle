<?php

namespace Ekyna\Bundle\CartBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * CartType
 *
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class CartType extends AbstractType
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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults(array(
                'data_class' => $this->dataClass,
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
