<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications')]
#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    public function __construct(
        private NotificationRepository $notificationRepository
    ) {}

    #[Route('', name: 'app_notifications')]
    public function index(): Response
    {
        $notifications = $this->notificationRepository->findByUser($this->getUser(), 50);

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/count', name: 'app_notifications_count', methods: ['GET'])]
    public function count(): JsonResponse
    {
        $count = $this->notificationRepository->countUnreadByUser($this->getUser());
        return $this->json(['count' => $count]);
    }

    #[Route('/{id}/read', name: 'app_notification_read', methods: ['POST'])]
    public function markAsRead(int $id): Response
    {
        $notification = $this->notificationRepository->find($id);

        if ($notification && $notification->getUser() === $this->getUser()) {
            $this->notificationRepository->markAsRead($notification);

            if ($notification->getLink()) {
                return $this->redirect($notification->getLink());
            }
        }

        return $this->redirectToRoute('app_notifications');
    }

    #[Route('/read-all', name: 'app_notifications_read_all', methods: ['POST'])]
    public function markAllAsRead(): Response
    {
        $this->notificationRepository->markAllAsRead($this->getUser());
        $this->addFlash('success', 'Toutes les notifications ont été marquées comme lues.');

        return $this->redirectToRoute('app_notifications');
    }
}
