<?php

namespace App\Controller;

use App\Datatables\AdminDatatable;
use App\Entity\User;
use App\Form\UserType;
use FOS\UserBundle\Model\UserManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class AdminController
 *
 * @package App\Controller
 * @Route("/admin", name="admin_")
 */
class AdminController extends Controller
{
    /**
     * @var UserManagerInterface
     */
    private $userManager;

    /**
     * AdminController constructor.
     * @param UserManagerInterface $userManager
     */
    public function __construct(UserManagerInterface $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * Display listing of existing Admin users
     *
     * @Route("/", name="index")
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     *
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        // or use the DatatableFactory
        $datatable = $this->get('sg_datatables.factory')->create(AdminDatatable::class);
        $datatable->buildDatatable();

        if ($isAjax) {
            $responseService = $this->get('sg_datatables.response');
            $responseService->setDatatable($datatable);
            $datatableQueryBuilder = $responseService->getDatatableQueryBuilder();

            /** @var QueryBuilder $qb */
            $qb = $datatableQueryBuilder->getQb();

            //add where conditions
            $qb->andWhere('user.enabled = :enabled');
            $qb->andWhere('user.roles LIKE :roles');

            //set parameters
            $qb->setParameter('enabled', true);
            $qb->setParameter('roles', '%"ROLE_ADMIN"%');

            return $responseService->getResponse();
        }

        return $this->render('admin/index.html.twig', [
            'datatable'         => $datatable,
            'clsAdmin'          => 'class=active',
            'clsAdminManage'    => 'class=active',
            'clsAdminSubMenu'   => 'style=display:block',
        ]);
    }

    /**
     * Display Add form for Admin user
     *
     * @param Request $request
     *
     * @Route("/add", name="add")
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function add(Request $request)
    {
        // build the form
        $user = new User();
        $user->addRole('ROLE_ADMIN');
        $user->setEnabled(1);
        $form = $this->createForm(UserType::class, $user);

        // handle the submit (will only happen on POST)
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setCreatedBy($this->getUser());
            $this->userManager->updateUser($user);
            $this->addFlash('success', 'Admin added successfully');

            return $this->redirectToRoute('admin_index');
        }

        return $this->render('admin/add.html.twig', [
            'form'              => $form->createView(),
            'clsAdmin'          => 'class=active',
            'clsAdminAdd'       => 'class=active',
            'clsAdminSubMenu'   => 'style=display:block',
        ]);
    }

    /**
     * Display edit form for existing admin user
     *
     * @param Request $request
     * @param User $user
     *
     * @Route("/edit/{id}", name="edit", requirements={"id": "\d+"}, options = {"expose" = true})
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function edit(Request $request, User $user)
    {
        $form = $this->createForm(UserType::class, $user, [
            'attr' => [
                'isFormEdit' => true
            ]
        ]);

        // handle the submit (will only happen on POST)
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setUpdatedBy($this->getUser());
            $this->userManager->updateUser($user);
            $this->addFlash('success', 'Admin updated successfully');

            return $this->redirectToRoute('admin_index');
        }

        return $this->render('admin/add.html.twig', [
            'form'              => $form->createView(),
            'formLabel'         => 'Edit Admin',
            'btnLabel'          => 'Update',
            'clsAdmin'          => 'class=active',
            'clsAdminAdd'       => 'class=active',
            'clsAdminSubMenu'   => 'style=display:block',
        ]);
    }

    /**
     * Deletes a admin entity.
     *
     * @param Request $request
     * @param User $user
     *
     * @Route("/delete/{id}", name="delete", requirements={"id": "\d+"}, options = {"expose" = true})
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete(Request $request, User $user)
    {
        $user->setEnabled(false);
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();
        $this->addFlash('success', 'Admin deleted successfully');

        return $this->redirectToRoute('admin_index');
    }

    /**
     * Bulk delete a client entity.
     *
     * @param Request $request
     *
     * @Route("/bulk/delete", name="bulk_delete")
     * @Method("POST")
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     *
     * @return Response
     */
    public function bulkDeleteAction(Request $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if ($isAjax) {
            $choices    = $request->request->get('data');
            $token      = $request->request->get('token');

            if (!$this->isCsrfTokenValid('multiselect', $token)) {
                throw new AccessDeniedException('The CSRF token is invalid.');
            }
            $em = $this->getDoctrine()->getManager();
            $repository = $em->getRepository('App:User');

            foreach ($choices as $choice) {
                $entity = $repository->find($choice['id']);
                $entity->setEnabled(false);
                $em->persist($entity);
            }
            $em->flush();

            return new Response('Success', Response::HTTP_OK);
        }

        return new Response('Bad Request', Response::HTTP_BAD_REQUEST);
    }
}
