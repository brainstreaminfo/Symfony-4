<?php

namespace App\Datatables;

use Sg\DatatablesBundle\Datatable\AbstractDatatable;
use Sg\DatatablesBundle\Datatable\Column\Column;
use Sg\DatatablesBundle\Datatable\Editable\TextEditable;
use Sg\DatatablesBundle\Datatable\Filter\NumberFilter;
use Sg\DatatablesBundle\Datatable\Filter\SelectFilter;
use Sg\DatatablesBundle\Datatable\Style;
use Sg\DatatablesBundle\Datatable\Column\ActionColumn;
use Sg\DatatablesBundle\Datatable\Column\MultiselectColumn;

/**
 * Class PostDatatable
 *
 * @package AppBundle\Datatables
 */
class AdminDatatable extends AbstractDatatable
{
    /**
     * {@inheritdoc}
     */
    public function buildDatatable(array $options = [])
    {
        $this->ajax->set([
            // cache for 10 pages
            'pipeline' => 10
        ]);
        $this->options->set([
            'classes'                           => 'cls-sgDatatable ' . Style::BOOTSTRAP_3_STYLE,
            'stripe_classes'                    => ['strip1', 'strip2', 'strip3'],
            'individual_filtering'              => false,
            'individual_filtering_position'     => 'head',
            'order'                             => [[1, 'desc']],
            'order_cells_top'                   => true,
            'search_in_non_visible_columns'     => true,
        ]);

        $this->columnBuilder
            ->add(
                null,
                MultiselectColumn::class,
                [
                    'start_html'    => '<div class="start_checkboxes">',
                    'end_html'      => '</div>',
                    'value'         => 'id',
                    'value_prefix'  => true,
                    'actions' => [
                        [
                            'route'             => 'admin_bulk_delete',
                            'icon'              => 'glyphicon glyphicon-remove',
                            'label'             => 'Delete Admins',
                            'attributes'        => [
                                'rel'   => 'tooltip',
                                'title' => 'Delete',
                                'class' => 'btn btn-danger btn-xs',
                                'role'  => 'button',
                            ],
                            'confirm'           => true,
                            'confirm_message'   => 'Are you sure you want to delete selected Admin(s) ?',
                            'start_html'        => '<div class="start_delete_action">',
                            'end_html'          => '</div>',
                            'render_if'         => function () {
                                return $this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN');
                            },
                        ],
                    ],
                ]
            )
            ->add('id', Column::class, [
                'title'         => 'Id',
                'searchable'    => true,
                'orderable'     => true,
                'filter'        => [NumberFilter::class, [
                    'classes'       => 'test1 test2',
                    'search_type'   => 'eq',
                    'cancel_button' => true,
                    'type'          => 'number',
                    'show_label'    => true,
                    'datalist'      => ['3', '50', '75']
                ]],
            ])
            ->add('name', Column::class, [
                'title'         => 'Name',
                'searchable'    => true,
                'orderable'     => true,
                'filter'        => [SelectFilter::class, [
                    'multiple'              => true,
                    'cancel_button'         => true,
                    'select_search_types'   => [
                        ''                  => null,
                        '2'                 => 'like',
                        '1'                 => 'eq',
                        'send_isNull'       => 'isNull',
                        'send_isNotNull'    => 'isNotNull'
                    ],
                    'select_options'        => [
                        ''                  => 'Any',
                        '2'                 => 'Title with the digit 2',
                        '1'                 => 'Title with the digit 1',
                        'send_isNull'       => 'is Null',
                        'send_isNotNull'    => 'is not Null'
                    ],
                ]],
                'editable'      => [TextEditable::class, [
                ]],
            ])
            ->add('username', Column::class, [
                'title'         => 'Username',
                'searchable'    => true,
                'orderable'     => true,
             ])
            ->add('email', Column::class, [
                'title'         => 'Email',
                'searchable'    => true,
                'orderable'     => true,
            ])
            ->add(null, ActionColumn::class, [
                'title'         => 'Actions',
                'start_html'    => '<div class="start_actions">',
                'end_html'      => '</div>',
                'actions'       => [
                    [
                        'route'     => 'admin_edit',
                        'label'     => 'Edit',
                        'route_parameters' => [
                            'id'    => 'id'
                        ],
                        "icon" => "glyphicon glyphicon-edit",
                        'attributes'    => [
                            'rel'     => 'tooltip',
                            'title'   => 'Edit',
                            'class'   => 'btn btn-default btn-xs',
                            'role'    => 'button'
                        ],
                    ],
                    [
                        'route'             => 'admin_delete',
                        'label'             => 'Delete',
                        'route_parameters'  => ['id' => 'id'],
                        "icon"              => "glyphicon glyphicon-remove",
                        'attributes'        => [
                            'rel'   => 'tooltip',
                            'title' => 'Delete',
                            'class' => 'btn btn-danger btn-xs fnConfirmRemove',
                            'role'  => 'button'
                        ]
                    ]
                ]
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntity()
    {
        return 'App\Entity\User';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'admin_datatable';
    }
}
