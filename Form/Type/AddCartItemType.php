<?php

namespace Ekyna\Bundle\CartBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Ekyna\Bundle\CoreBundle\Form\DataTransformer\ObjectToIdentifierTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * AddCartItemType
 *
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class AddCartItemType extends AbstractType
{
    protected $optionsConfiguration;

    public function __construct(array $optionsConfiguration)
    {
        $this->optionsConfiguration = $optionsConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new ObjectToIdentifierTransformer($options['em']->getRepository('EkynaProductBundle:AbstractProduct'));

        $builder->
            add(
                $builder->create('product', 'hidden')->addModelTransformer($transformer)
            )
            ->add('quantity', 'integer', array('attr' => array('min' => 1)))
            ->add('submit', 'submit', array(
        	    'label' => 'Ajouter au panier'
            ))
            ->setAction($options['action'])
        ;

        $builder->addEventListener(FormEvents::POST_SET_DATA, function(FormEvent $event){
            $data = $event->getData();
            $form = $event->getForm();
            $product = $data['product'];

            if ($product->hasOptions()) {
                $groups = $product->getOptionsGroups();
                foreach ($groups as $group) {
                    if(! array_key_exists($group, $this->optionsConfiguration)) {
                        throw new \RuntimeException(sprintf('Undefined option configuration "%s".', $group));
                    }
                    $config = $this->optionsConfiguration[$group];
                    $form->add('option-'.$group, 'entity', array(
                        'label' => $this->optionsConfiguration[$group]['label'],
                        'required' => false,
                        'empty_value' => 'Choisissez une option',
                        'attr' => array(
                    	    'placeholder' => 'Choisissez une option',
                        ),
                        'class' => $this->optionsConfiguration[$group]['class'],
                        'query_builder' => function(EntityRepository $er) use ($product, $group) {
                            $qb = $er->createQueryBuilder('o');
                            return $qb
                                ->andWhere($qb->expr()->eq('o.product', ':product'))
                                ->andWhere($qb->expr()->eq('o.group', ':group'))
                                ->setParameter('product', $product)
                                ->setParameter('group', $group)
                            ;
                        },
                    )); 
                }
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
                'em'     => null,
                'action' => null
            ))
            ->setRequired(array('em', 'action'))
            ->setAllowedTypes(array(
            	'em'     => 'Doctrine\ORM\EntityManager',
                'action' => 'string'
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
    	return 'ekyna_add_cart_item';
    }
}
