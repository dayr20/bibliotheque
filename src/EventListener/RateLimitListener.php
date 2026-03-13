<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Psr\Cache\CacheItemPoolInterface;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 20)]
class RateLimitListener
{
    // Routes protégées avec leurs limites : [max tentatives, période en secondes]
    private const RATE_LIMITS = [
        'app_login' => [5, 300],      // 5 tentatives / 5 min
        'app_register' => [3, 600],    // 3 inscriptions / 10 min
        'app_verify_email' => [5, 300], // 5 vérifications / 5 min
    ];

    public function __construct(
        private CacheItemPoolInterface $cache
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->isMethod('POST')) {
            return;
        }

        $route = $request->attributes->get('_route');

        if (!isset(self::RATE_LIMITS[$route])) {
            return;
        }

        [$maxAttempts, $period] = self::RATE_LIMITS[$route];
        $ip = $request->getClientIp();
        $cacheKey = 'rate_limit_' . md5($route . '_' . $ip);

        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            $data = $cacheItem->get();
            $attempts = $data['attempts'];
            $firstAttemptAt = $data['first_attempt_at'];

            // Si la période est écoulée, on reset
            if ((time() - $firstAttemptAt) > $period) {
                $attempts = 0;
            }

            if ($attempts >= $maxAttempts) {
                $remainingSeconds = $period - (time() - $firstAttemptAt);
                $remainingMinutes = (int) ceil($remainingSeconds / 60);

                $message = "Trop de tentatives. Réessayez dans {$remainingMinutes} minute(s).";

                // Réponse JSON pour les endpoints API (register, verify)
                if (in_array($route, ['app_register', 'app_verify_email'])) {
                    $event->setResponse(new JsonResponse(
                        ['error' => $message],
                        Response::HTTP_TOO_MANY_REQUESTS
                    ));
                    return;
                }

                // Réponse flash + redirect pour le login
                $request->getSession()->getFlashBag()->add('error', $message);
                $event->setResponse(new \Symfony\Component\HttpFoundation\RedirectResponse('/login'));
                return;
            }

            $attempts++;
        } else {
            $attempts = 1;
            $firstAttemptAt = time();
        }

        $cacheItem->set([
            'attempts' => $attempts,
            'first_attempt_at' => $firstAttemptAt ?? time(),
        ]);
        $cacheItem->expiresAfter($period);
        $this->cache->save($cacheItem);
    }
}
