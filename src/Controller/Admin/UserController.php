<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\Roles;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('', name: 'admin_users_index')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/users/index.html.twig', [
            'users' => $userRepository->findAll()
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $newRoles = $request->request->all()['roles'] ?? [];
            $user->setRoles($newRoles);
            $entityManager->flush();

            $this->addFlash('success', 'Les rôles de l\'utilisateur ont été mis à jour.');
            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/users/edit.html.twig', [
            'user' => $user,
            'available_roles' => Roles::getAllRoles()
        ]);
    }

    #[Route('/{id}/toggle-verification', name: 'admin_user_toggle_verification', methods: ['POST'])]
    public function toggleVerification(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setIsVerified(!$user->isVerified());
        $entityManager->flush();

        $status = $user->isVerified() ? 'vérifié' : 'non vérifié';
        $this->addFlash('success', "Le compte est maintenant $status.");

        return $this->redirectToRoute('admin_users_index');
    }
}
