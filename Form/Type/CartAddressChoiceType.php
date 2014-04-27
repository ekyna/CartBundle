<?php

namespace Ekyna\Bundle\CartBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * CartAdressChoiceType.
 *
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class CartAddressChoiceType extends AbstractType
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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults(array(
                'class'    => $this->dataClass,
                'property'      => 'id',
                'expanded'      => true,
            ))
            ->setAllowedTypes(array(
                'class'    => 'string',
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'entity';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ekyna_cart_address_choice';
    }
}
