<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;

class NotificationService
{
    public function __construct(
        private NotificationRepository $notificationRepository
    ) {}

    public function notify(User $user, string $type, string $title, string $message, ?string $link = null): Notification
    {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setType($type);
        $notification->setTitle($title);
        $notification->setMessage($message);
        $notification->setLink($link);

        $this->notificationRepository->save($notification, true);

        return $notification;
    }

    public function notifyNewChapter(User $user, string $mangaTitle, int $chapterNumber, string $link): Notification
    {
        return $this->notify(
            $user,
            Notification::TYPE_NEW_CHAPTER,
            "Nouveau chapitre disponible",
            "Le chapitre $chapterNumber de \"$mangaTitle\" est disponible !",
            $link
        );
    }

    public function notifyMangaUpdate(User $user, string $mangaTitle, string $link): Notification
    {
        return $this->notify(
            $user,
            Notification::TYPE_MANGA_UPDATE,
            "Manga mis à jour",
            "\"$mangaTitle\" a été mis à jour.",
            $link
        );
    }

    public function notifySystem(User $user, string $title, string $message): Notification
    {
        return $this->notify($user, Notification::TYPE_SYSTEM, $title, $message);
    }
}
