<?php

include __DIR__ . '/../vendor/autoload.php';

use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
use Discord\WebSockets\Event as DiscordEvent;
use Discord\WebSockets\Intents;
use React\EventLoop\Loop;
use Tricky\BestBot\event;

// ----------------------------------------------------------------
const BOT_ID = 872546787360137246;
const MODERATOR_GROUP_ID = 737730859951718402;
const CHANNEL_ID = 886218298516209664;
const PLAYER = "784532360887664670";
const TRICKY = 157579105846558720;
// ----------------------------------------------------------------
$config = require __DIR__ . "/config/config.php";
/** @var Discord $discord */
$discord = new Discord([
    'token' => $config["key"],
    'intents' => Intents::getDefaultIntents(),
]);

$discord->on('ready', function ($discord) {
    echo "Bot is ready!", PHP_EOL;
    // Listen for messages.
    $discord->on('message', function ($message, $discord) {
        echo "{$message->author->username}: {$message->content}", PHP_EOL;
    });
});
date_default_timezone_set('Europe/Copenhagen');
$timezone = new DateTimeZone("Europe/Copenhagen");
/** @var array event $events */
$recuringEvents[] = new event(new DateTime("2021-08-02T20:00:00+02:00",$timezone), "Trap in 1 hour! <@&" . PLAYER . ">", 172800, CHANNEL_ID);
$recuringEvents[] = new event(new DateTime("2021-08-02T20:30:00+02:00",$timezone), "Trap in 30 minutes! <@&" . PLAYER . ">", 172800, CHANNEL_ID);
$recuringEvents[] = new event(new DateTime("2021-08-02T20:55:00+02:00",$timezone), "Trap in 5 minutes! <@&" . PLAYER . ">", 172800, CHANNEL_ID);
$recuringEvents[] = new event(new DateTime("2021-08-02T21:00:00+02:00",$timezone), "Trap now! <@&" . PLAYER . ">", 172800, CHANNEL_ID);
$recuringEvents[] = new event(new DateTime("2021-08-05T20:00:00+02:00",$timezone), "Horde in 1 hour! <@&" . PLAYER . ">", 1209600, CHANNEL_ID);
$recuringEvents[] = new event(new DateTime("2021-08-05T20:30:00+02:00",$timezone), "Horde in 30 minutes! <@&" . PLAYER . ">", 1209600, CHANNEL_ID);
$recuringEvents[] = new event(new DateTime("2021-08-05T20:55:00+02:00",$timezone), "Horde in 5 minutes! <@&" . PLAYER . ">", 1209600, CHANNEL_ID);
$recuringEvents[] = new event(new DateTime("2021-08-05T21:00:00+02:00",$timezone), "Horde now! <@&" . PLAYER . ">", 1209600, CHANNEL_ID);
$recuringEvents[] = new event(new DateTime("2021-08-03T20:00:00+02:00",$timezone), "Horde in 1 hour! <@&" . PLAYER . ">", 1209600, CHANNEL_ID);
$recuringEvents[] = new event(new DateTime("2021-08-03T20:30:00+02:00",$timezone), "Horde in 30 minutes! <@&" . PLAYER . ">", 1209600, CHANNEL_ID);
$recuringEvents[] = new event(new DateTime("2021-08-03T20:55:00+02:00",$timezone), "Horde in 5 minutes! <@&" . PLAYER . ">", 1209600, CHANNEL_ID);
$recuringEvents[] = new event(new DateTime("2021-08-03T21:00:00+02:00",$timezone), "Horde now! <@&" . PLAYER . ">", 1209600, CHANNEL_ID);

$staticEvents = [];

$eventFile = __DIR__ . "/storage/events.txt";

function loadEvents()
{
    global $staticEvents, $eventFile;
    $staticEvents = [];
    if (!file_exists($eventFile)) {
        file_put_contents($eventFile, serialize([]));
    }
    $fileContent = file_get_contents($eventFile);
    $staticEvents = unserialize($fileContent);
    /** @var event $staticEvent */
    foreach ($staticEvents as $staticEvent) {
        $staticEvent->calculateNext();
    }
    $count = count($staticEvents);
    echo "Loaded {$count} events to {$eventFile}\n";
}

function saveEvents()
{
    global $staticEvents, $eventFile;
    $staticEventsTmp = [];
    /** @var event $staticEvent */
    foreach ($staticEvents as $staticEvent) {
        if ($staticEvent->first->getTimestamp() > (new DateTime("now"))->getTimestamp()) {
            $staticEventsTmp[] = $staticEvent;
        }
    }
    $content = serialize($staticEventsTmp);
    file_put_contents($eventFile, $content);
    $count = count($staticEventsTmp);
    echo "Saved {$count} events to {$eventFile}\n";
}

$discord->on('ready', function (Discord $discord) {
    loadEvents();
    Loop::addPeriodicTimer(10, function () {
        loop();
    });

});

function loop()
{
    global $recuringEvents, $discord, $staticEvents;
    $allEvents = array_merge($recuringEvents, $staticEvents);
    echo "loop\n";
    /** @var event $event */
    foreach ($allEvents as $event) {
        if (isset($event->nextPlay)) {
            // If the current timestamp is greater than the NextPlay timestamp the message needs to be sent
            $timeToNext = (new DateTime("now"))->getTimestamp() - $event->nextPlay->getTimestamp();
            if ($timeToNext >= 0) {
                echo "Sending message\n";
                //784532360887664670 StateOfSurvivalPlayer
                $message = MessageBuilder::new()->setContent($event->message);
                $discord->getChannel($event->channel)->sendMessage($message)->done(function (Message $message) {
                    echo "Message sent!\n";
                });
                $event->calculateNext();
            }
        }
    }
}


// Commands


$help = [
    "\n",
    "!bb ac {Protected time left, ex: 3:1:45 = 3 days, 1 hour and 45 minutes}",
    " - Format for date must contain days, hours and minutes. In case there is 0 days it must still be included as ex: 0:1:45",
    "!bb rr {Time until start, ex: 3:1:45 = 3 days, 1 hour and 45 minutes}",
    " - Format for date must contain days, hours and minutes. In case there is 0 days it must still be included as ex: 0:1:45",
    "!bb cc {Time until start, ex: 3:1:45 = 3 days, 1 hour and 45 minutes}",
    " - Format for date must contain days, hours and minutes. In case there is 0 days it must still be included as ex: 0:1:45",
    "!bb kill",
    " - Kills Best Bot in case it fails or starts spamming, this will not restart the bot, Contact Tricky!",
    "!bb delay {amount of events to delay} {Number of minutes to delays}",
    "!bb events",
    " - See full list of upcomming recurring events",
    "!bb staticevents",
    " - See full list of upcomming static events",
];

$discord->on(DiscordEvent::MESSAGE_CREATE, function (Message $message, Discord $discord) {
    global $help, $staticEvents, $recuringEvents;

    $id = $message->author->user->id;


    // If not the bot continue, The bot does not take it's own meesages into account
    if ($id != BOT_ID) {
        // Check if the user has the correct group
        $isModerator = $message->author->roles->has(MODERATOR_GROUP_ID);
        $isTricky = $message->author->id == TRICKY;
        // Only look at commands that start with !bb (best bot command prefix)
        $isCommand = substr(strtolower($message->content), 0, 3) === "!bb";
        // Sort the events by how long there is till the event needs to trigger
        usort($recuringEvents, function ($a, $b) {
            return event::cmp($a, $b);
        });
        // Only check commands
        if ($isCommand) {
            $commands = explode(" ", strtolower($message->content));
            $arguments = count($commands);
            if ($isModerator) {
                switch ($commands[1]) {
                    case "help":
                        $message->reply(implode("\n", $help));
                        break;
                    case "ac":
                        if ($arguments != 4) {
                            $message->reply("Invalid number of arguments, use \"!bb help\" for help");
                        } else {
                            $acNumber = $commands[2];
                            $time = explode(":", $commands[3]);
                            if (count($time) === 3) {
                                $minutes = ((int)$time[0] * 60 * 24) + ((int)$time[1] * 60) + (int)$time[2];
                                $minutesMinus60 = $minutes - 60;
                                $minutesMinus30 = $minutes - 30;
                                $minutesMinus5 = $minutes - 5;
                                $eventNow = (new DateTime("now"))->add(getDatetimeInterval($minutes));
                                $eventMinus60 = (new DateTime("now"))->add(getDatetimeInterval($minutesMinus60));
                                $eventMinus30 = (new DateTime("now"))->add(getDatetimeInterval($minutesMinus30));
                                $eventMinus5 = (new DateTime("now"))->add(getDatetimeInterval($minutesMinus5));
                                $staticEvents[] = new event($eventMinus60, "AC$acNumber in 1 hour! <@&" . PLAYER . ">", 0, CHANNEL_ID);
                                $staticEvents[] = new event($eventMinus30, "AC$acNumber in 30 minutes! <@&" . PLAYER . ">", 0, CHANNEL_ID);
                                $staticEvents[] = new event($eventMinus5, "AC$acNumber in 5 minutes! <@&" . PLAYER . ">", 0, CHANNEL_ID);
                                $staticEvents[] = new event($eventNow, "AC$acNumber now! <@&" . PLAYER . ">", 0, CHANNEL_ID);
                                saveEvents();
                                $message->reply("Event created!");
                            } else {
                                $message->reply("Invalid arguments, use !bb help to see how to use the commands!");
                            }
                        }
                        break;
                    case "kill":
                        if ($arguments != 2) {
                            $message->reply("Invalid number of arguments, use \"!bb help\" for help");
                        } else {
                            exit(1);
                        }
                        break;
                    case "delay":
                        if ($arguments != 4) {
                            $message->reply("Invalid number of arguments, use \"!bb help\" for help");
                        } else {
                            $count = $commands[2];
                            $minutes = intval($commands[3]);
                            $text = ["Events: "];
                            for ($i = 0; $i < $count; $i++) {
                                /** @var event $event */
                                $event = $recuringEvents[$i];
                                if ($minutes > 0) {
                                    $event->nextPlay->add(getDatetimeInterval(abs($minutes)));
                                } elseif ($minutes < 0) {
                                    $event->nextPlay->sub(getDatetimeInterval(abs($minutes)));

                                }
                                echo "Changed \"$event->message\" by {$minutes}\n";
                                $text[] = "Changed \"$event->message\" by {$minutes} minutes";
                            }
                            $message->reply(implode("\n", $text));
                        }

                        break;
                    case "events":
                        if ($arguments != 2) {
                            $message->reply("Invalid number of arguments, use \"!bb help\" for help");
                        }
                        $text = [
                            "Events: "
                        ];
                        $count = 1;
                        /** @var event $event */
                        foreach ($recuringEvents as $event) {
                            $text[] = "[{$count}] \"{$event->message}\" - {$event->nextPlay->format('c')}";
                            $count++;
                        }
                        $message->reply(implode("\n", $text));
                        break;
                    case "staticevents":
                        if ($arguments != 2) {
                            $message->reply("Invalid number of arguments, use \"!bb help\" for help");
                        }
                        $text = [
                            "Events: "
                        ];
                        $count = 1;
                        /** @var event $event */
                        foreach ($staticEvents as $event) {
                            $text[] = "[{$count}] \"{$event->message}\" - {$event->nextPlay->format('c')}";
                            $count++;
                        }
                        $message->reply(implode("\n", $text));
                        break;
                    case "time":
                        $time = new DateTime("now");
                        $message->reply("Current time: ".$time->format("c"));
                        break;
                    case "ke":
                        if ($arguments != 3) {
                            $message->reply("Invalid number of arguments, use \"!bb help\" for help");
                        } else {
                            $time = explode(":", $commands[2]);
                            $countTime = count($time) === 3;
                            if ($countTime) {
                                $minutes = ((int)$time[0] * 60 * 24) + ((int)$time[1] * 60) + (int)$time[2];
                                $minutesMinus60 = $minutes - 60;
                                $event24hour = $minutes - (60*24);
                                $event3hour = $minutes - (60*3);
                                $eventNow = (new DateTime("now"))->add(getDatetimeInterval($minutes));
                                $eventMinus60 = (new DateTime("now"))->add(getDatetimeInterval($minutesMinus60));
                                $eventMinus24hour = (new DateTime("now"))->add(getDatetimeInterval($event24hour));
                                $eventMinus48hour = (new DateTime("now"))->add(getDatetimeInterval($event24hour*2));
                                $eventMinus6hour = (new DateTime("now"))->add(getDatetimeInterval($event3hour*2));
                                $eventMinus3hour = (new DateTime("now"))->add(getDatetimeInterval($event3hour));
                                $staticEvents[] = new event($eventNow, "KILL EVENT HAS STARTED, LAST CHANCE TO FLARE! <@&" . PLAYER . ">", 0, CHANNEL_ID);
                                $staticEvents[] = new event($eventMinus60, "KILL EVENT STARTS IN 1 HOUR, FLARE NOW!! <@&" . PLAYER . ">", 0, CHANNEL_ID);
                                $staticEvents[] = new event($eventMinus3hour, "KILL EVENT STARTS IN 3 HOURS <@&" . PLAYER . ">", 0, CHANNEL_ID);
                                $staticEvents[] = new event($eventMinus6hour, "KILL EVENT STARTS IN 6 HOURS <@&" . PLAYER . ">", 0, CHANNEL_ID);
                                $staticEvents[] = new event($eventMinus24hour, "KILL EVENT STARTS IN 24 HOURS <@&" . PLAYER . ">", 0, CHANNEL_ID);
                                $staticEvents[] = new event($eventMinus48hour, "KILL EVENT STARTS IN 48 HOURS <@&" . PLAYER . ">", 0, CHANNEL_ID);
                                saveEvents();
                                $message->reply("Event created!");
                            } else {
                                $message->reply("Invalid arguments, use !bb help to see how to use the commands!");
                            }
                        }
                        break;
                    case "rr":
                        if ($arguments != 3) {
                            $message->reply("Invalid number of arguments, use \"!bb help\" for help");
                        } else {
                            $time = explode(":", $commands[2]);
                            $countTime = count($time) === 3;
                            if ($countTime) {
                                $minutes = ((int)$time[0] * 60 * 24) + ((int)$time[1] * 60) + (int)$time[2];
                                $minutesMinus60 = $minutes - 60;
                                $minutesMinus30 = $minutes - 30;
                                $minutesMinus5 = $minutes - 5;
                                $eventNow = (new DateTime("now"))->add(getDatetimeInterval($minutes));
                                $eventMinus60 = (new DateTime("now"))->add(getDatetimeInterval($minutesMinus60));
                                $eventMinus30 = (new DateTime("now"))->add(getDatetimeInterval($minutesMinus30));
                                $eventMinus5 = (new DateTime("now"))->add(getDatetimeInterval($minutesMinus5));
                                $staticEvents[] = new event($eventMinus60, "Reservoir Raid in 1 hour! <@&" . PLAYER . ">", 0, CHANNEL_ID);
                                $staticEvents[] = new event($eventMinus30, "Reservoir Raid in 30 minutes! <@&" . PLAYER . ">", 0, CHANNEL_ID);
                                $staticEvents[] = new event($eventMinus5, "Reservoir Raid in 5 minutes, GET ONLINE NOW!! <@&" . PLAYER . ">", 0, CHANNEL_ID);
                                $staticEvents[] = new event($eventNow, "Reservoir Raid NOW!!!! <@&" . PLAYER . ">", 0, CHANNEL_ID);
                                saveEvents();
                                $message->reply("Event created!");
                            } else {
                                $message->reply("Invalid arguments, use !bb help to see how to use the commands!");
                            }
                        }
                        break;
                    case "cc":
                        if ($arguments != 3) {
                            $message->reply("Invalid number of arguments, use \"!bb help\" for help");
                        } else {
                            $time = explode(":", $commands[2]);
                            if (count($time) === 3) {
                                $minutes = ((int)$time[0] * 60 * 24) + ((int)$time[1] * 60) + (int)$time[2];
                                $minutesMinus60 = $minutes - 60;
                                $minutesMinus30 = $minutes - 30;
                                $minutesMinus5 = $minutes - 5;
                                $eventNow = (new DateTime("now"))->add(getDatetimeInterval($minutes));
                                $eventMinus60 = (new DateTime("now"))->add(getDatetimeInterval($minutesMinus60));
                                $eventMinus30 = (new DateTime("now"))->add(getDatetimeInterval($minutesMinus30));
                                $eventMinus5 = (new DateTime("now"))->add(getDatetimeInterval($minutesMinus5));
                                $staticEvents[] = new event($eventMinus60, "Capital Clash starts in 1 hour! <@&" . PLAYER . ">", 0, CHANNEL_ID);
                                $staticEvents[] = new event($eventMinus30, "Capital Clash starts in 30 minutes! <@&" . PLAYER . ">", 0, CHANNEL_ID);
                                $staticEvents[] = new event($eventMinus5, "Capital Clash starts in 5 minutes <@&" . PLAYER . ">", 0, CHANNEL_ID);
                                $staticEvents[] = new event($eventNow, "Capital Clash starts NOW!!!!! <@&784532360887664670>", 0, CHANNEL_ID);
                                saveEvents();
                                $message->reply("Event created!");
                            } else {
                                $message->reply("Invalid arguments, use !bb help to see how to use the commands!");
                            }
                        }
                        break;
                    case "beat":
                        if ($isTricky) {
                            $message->reply("Tricky beat the shit out of me because I acted like Bad-Bot");
                        } else {
                            $message->reply("Only Tricky is allowed to beat me!");
                        }
                        break;
                    default:
                        $message->reply("Unknown command - use \"!bb help\" for help!");
                        break;
                }
            } else {
                $message->reply("You are not authorised to execute the \"{$commands[1]}\" command!");
            }
        }
    }
});

function getDatetimeInterval($minutes)
{
    return new DateInterval("PT{$minutes}M");
}


$discord->run();
