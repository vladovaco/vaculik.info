<?php

declare(strict_types=1);

namespace Modules\Notifications\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\I18n\Time;
use Modules\Notifications\Models\NotificationModel;
use Modules\Notifications\Models\PushSubscriptionModel;
use Modules\Notifications\Services\Notifier;

class NotificationController extends BaseController
{
    public function index(): string
    {
        $notifications = model(NotificationModel::class);
        $list          = $notifications->recent(auth()->id());
        $notifications->markAllRead(auth()->id());

        return view('Modules\Notifications\Views\index', ['title' => 'Upozornenia', 'notifications' => $list]);
    }

    public function open(int $id): RedirectResponse
    {
        $notifications = model(NotificationModel::class);
        $n             = $notifications->find($id);
        if ($n === null || $n->user_id !== auth()->id()) {
            return redirect()->route('notifications');
        }
        $notifications->update($n->id, ['read_at' => Time::now()->format('Y-m-d H:i:s')]);

        return redirect()->to($n->url ?: url_to('notifications'));
    }

    public function readAll(): RedirectResponse
    {
        model(NotificationModel::class)->markAllRead(auth()->id());

        return redirect()->route('notifications');
    }

    public function settings(): string
    {
        $notifier = new Notifier();

        return view('Modules\Notifications\Views\settings', [
            'title'       => 'Nastavenia upozornení',
            'push'        => $notifier->setting(auth()->user(), 'push', true),
            'email'       => $notifier->setting(auth()->user(), 'email', false),
            'pushEnabled' => config('Push')->enabled(),
            'devices'     => model(PushSubscriptionModel::class)->forUser(auth()->id()),
        ]);
    }

    public function saveSettings(): RedirectResponse
    {
        $notifier = new Notifier();
        $notifier->saveSetting(auth()->user(), 'push', (bool) $this->request->getPost('push'));
        $notifier->saveSetting(auth()->user(), 'email', (bool) $this->request->getPost('email'));

        return redirect()->route('notifications.settings')->with('message', 'Nastavenia sú uložené.');
    }

    /**
     * Called by public/assets/push.js with the browser's PushSubscription JSON.
     */
    public function subscribe(): ResponseInterface
    {
        $data = $this->request->getJSON(true);
        if (! is_array($data) || empty($data['endpoint']) || empty($data['keys']['p256dh']) || empty($data['keys']['auth'])) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'invalid subscription']);
        }
        model(PushSubscriptionModel::class)->upsert(auth()->id(), $data, $this->request->getUserAgent()->getAgentString());

        return $this->response->setJSON(['ok' => true]);
    }

    public function unsubscribe(): ResponseInterface
    {
        $data = $this->request->getJSON(true);
        if (is_array($data) && ! empty($data['endpoint'])) {
            model(PushSubscriptionModel::class)->removeEndpoint((string) $data['endpoint']);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    public function test(): RedirectResponse
    {
        $ok = (new Notifier())->notify(
            auth()->user(),
            (int) service('householdContext')->householdId(),
            'test:' . time(),
            'Skúšobné upozornenie',
            'Ak toto vidíte na telefóne, upozornenia fungujú.',
            url_to('notifications'),
            Notifier::LEVEL_INFO,
        );

        return redirect()->route('notifications.settings')->with('message', $ok ? 'Skúšobné upozornenie bolo odoslané.' : 'Upozornenie sa nepodarilo vytvoriť.');
    }
}
