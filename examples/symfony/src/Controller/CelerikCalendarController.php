<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Meeting;
use App\Repository\MeetingRepository;
use Celerik\Ui\Adapters\Symfony\DoctrineEventSource;
use Celerik\Ui\Components\Scheduler;
use Celerik\Ui\Data\Event;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Scheduler example. Changes are POSTed as JSON with the token in the X-CSRF-TOKEN
 * header, checked by checkCsrf(). Add authorization (voters, #[IsGranted]) to these
 * routes in a real application.
 */
#[Route('/celerik/scheduler', name: 'celerik_scheduler')]
final class CelerikCalendarController extends AbstractController
{
    private const CSRF_ID = 'celerik_scheduler';
    private const FIELDS = ['start' => 'startsAt', 'end' => 'endsAt'];

    public function __construct(
        private readonly MeetingRepository $meetings,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: '', methods: ['GET'])]
    public function show(CsrfTokenManagerInterface $csrf): Response
    {
        return $this->render('celerik/scheduler.html.twig', [
            'scheduler' => $this->scheduler()->csrf($csrf->getToken(self::CSRF_ID)->getValue()),
        ]);
    }

    #[Route('/events', name: '_events', methods: ['GET'])]
    public function events(Request $request): JsonResponse
    {
        try {
            $source = new DoctrineEventSource($this->meetings->createQueryBuilder('m'), self::FIELDS);

            return $this->json($this->scheduler()->respond($source, $request->query->all()));
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/events/create', name: '_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        return $this->checkCsrf($request) ?? $this->save(null, $request);
    }

    #[Route('/events/update', name: '_update', methods: ['POST'])]
    public function update(Request $request): JsonResponse
    {
        if ($error = $this->checkCsrf($request)) {
            return $error;
        }

        $meeting = $this->meetings->find((int) ($request->toArray()['id'] ?? 0));

        return $meeting === null
            ? $this->json(['error' => 'Unknown event.'], Response::HTTP_NOT_FOUND)
            : $this->save($meeting, $request);
    }

    #[Route('/events/delete', name: '_delete', methods: ['POST'])]
    public function delete(Request $request): JsonResponse
    {
        if ($error = $this->checkCsrf($request)) {
            return $error;
        }

        $meeting = $this->meetings->find((int) ($request->toArray()['id'] ?? 0));
        if ($meeting !== null) {
            $this->em->remove($meeting);
            $this->em->flush();
        }

        return $this->json(['data' => null]);
    }

    private function save(?Meeting $meeting, Request $request): JsonResponse
    {
        try {
            // Event::fromArray() validates the title, dates (ISO 8601, end >= start), color and URL.
            $payload = $request->toArray();
            $event = Event::fromArray(array_intersect_key($payload, array_flip(['title', 'start', 'end', 'allDay'])));
        } catch (InvalidArgumentException | JsonException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Doctrine stores a date's wall-clock time and drops its offset: convert to the
        // server time zone first, or "14:00+01:00" would be saved as 14:00.
        $timeZone = new DateTimeZone(date_default_timezone_get());
        $start = $event->start->setTimezone($timeZone);
        $end = $event->end?->setTimezone($timeZone);

        if ($meeting === null) {
            $meeting = new Meeting($event->title, $start, $end, $event->allDay);
            $this->em->persist($meeting);
        } else {
            $meeting->reschedule($event->title, $start, $end, $event->allDay);
        }
        $this->em->flush();

        return $this->json(['data' => new Event($event->title, $start, $end, $event->allDay, $meeting->getId())]);
    }

    private function checkCsrf(Request $request): ?JsonResponse
    {
        $token = (string) $request->headers->get('X-CSRF-TOKEN');

        return $this->isCsrfTokenValid(self::CSRF_ID, $token)
            ? null
            : $this->json(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
    }

    private function scheduler(): Scheduler
    {
        return Scheduler::make('meetings')
            ->title('Meetings')
            ->url($this->generateUrl('celerik_scheduler_events'))
            ->view('week')
            ->firstDay(1)
            ->businessHours('08:00', '18:00')
            ->createUrl($this->generateUrl('celerik_scheduler_create'))
            ->updateUrl($this->generateUrl('celerik_scheduler_update'))
            ->deleteUrl($this->generateUrl('celerik_scheduler_delete'));
    }
}
