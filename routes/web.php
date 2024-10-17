<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::post('/hook', function (Request $request) {
    if ($request->input('event') == "ORDER.CREATED") {
        $eventId = $request->input('payload.event_summary.id');
        $eventName = $request->input('payload.event_summary.name');
        $issuedTickets = $request->input('payload.issued_tickets');

        $response = Http::withBasicAuth(env('TT_API_KEY'), '')
            ->acceptJson()
            ->get('https://api.tickettailor.com/v1/events/'.$eventId);
        $ticketTypes = $response['ticket_types'];
        if($response->status() != 200) {
            return response()->setStatusCode(500);
        }

        $ticketCounts = [];
        $embedFields = [];

        foreach($issuedTickets as $ticket) {
            if (array_key_exists($ticket['ticket_type_id'], $ticketCounts)) {
                $ticketCounts[$ticket['ticket_type_id']] = $ticketCounts[$ticket['ticket_type_id']] + 1;
            } else {
                $ticketCounts[$ticket['ticket_type_id']] = 1;
            }
        }

        foreach ($ticketTypes as $ticketType) {
            if (array_key_exists($ticketType['id'], $ticketCounts)) {
                $ticketsSold = $ticketType['quantity_total'] - $ticketType['quantity'];
                array_push($embedFields,
                    [
                        "name" => $ticketType['name']." x".$ticketCounts[$ticketType['id']],
                        "value" =>  $ticketType['quantity']."/".$ticketType['quantity_total']." remaining, ".$ticketsSold." sold",
                    ]);
            }
        }

        Http::post(env('DISCORD_HOOK_URL'),
        [
            "username" => "Ticket Tailor",
            "embeds" => [
                [
                    "title" => "New Order - ".$eventName,
                    "color" => 12845311,
                    "fields" => $embedFields
                ]
            ]
        ]);

    }
});
