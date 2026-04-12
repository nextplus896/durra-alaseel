<?php

namespace App\Http\Helpers;

use App\Providers\Admin\BasicSettingsProvider;
use Exception;
use Illuminate\Support\Facades\Log;
use Pusher\PushNotifications\PushNotifications;


class PushNotificationHelper {

    public $data;
    public $users;

    public $provider;
    public $provider_name;
    public $provider_config;

    public $user_type;

    public $n_icon;
    public $n_title;
    public $n_desc;

    public function __construct(array $data = [])
    {
        $this->config();

        if(isset($data['users'])){
            $this->users = $data['users'];
        }

        if(isset($data['user_type'])){
            $this->user_type = $data['user_type'];
        }
    }

    public function registerProvider()
    {
        return [
            'pusher'    => "pusherConfig"
        ];
    }

    public function registerSend()
    {
        return [
            'pusher'    => "pusherSend"
        ];
    }

    public function unsubscribeRegister()
    {
        return [
            'pusher'    => "pusherUnsubscribe"
        ];
    }

    public function prepareUnauthorize(array $users, array $data)
    {
        try{
            $user_guard = $data['user_guard'];
            $fav = get_fav();


            $this->data                 = $data;
            $this->users                = $users;
            $this->n_icon               = $fav;
            $this->user_type            = $data['user_type'];
            $this->n_title              = $data['title'];
            $this->n_desc               = $data['desc'];

            return $this;
        }catch(Exception $e){}
    }
    public function prepareApi(array $users, array $data)
    {
       try{

        $fav = get_fav();


        $this->data                 = $data;
        $this->users                = $users;
        $this->n_icon               = $fav;
        $this->user_type            = $data['user_type'];
        $this->n_title              = $data['title'];
        $this->n_desc               = $data['desc'];

        return $this;
       }catch(Exception $e){}
    }
    public function prepare(array $users, array $data)
    {
       try{

        $fav = get_fav();


        $this->data                 = $data;
        $this->users                = $users;
        $this->n_icon               = $fav;
        $this->user_type            = $data['user_type'];
        $this->n_title              = $data['title'];
        $this->n_desc               = $data['desc'];

        return $this;
       }catch(Exception $e){}
    }

    public function send()
    {
        $provider_name = $this->provider_name;

        if(array_key_exists($provider_name, $this->registerSend())) {
            $method = $this->registerSend()[$provider_name];
            return $this->$method();
        }

        throw new Exception(__("Oops! Notification provider send method not declared"));
    }

    public function config()
    {
        $settings = BasicSettingsProvider::get();
        if(!$settings) throw new Exception(__("Oops! Configuration failed. Settings not found!"));

        $push_n_config = $settings->push_notification_config;
        if(!$push_n_config) throw new Exception(__("Oops! Failed to send push notification. Please configure push settings"));

        $provider_name = $push_n_config->method ?? "";
        $this->provider_name = $provider_name;

        if(array_key_exists($provider_name, $this->registerProvider())) {
            $method = $this->registerProvider()[$provider_name];
            return $this->$method(json_decode(json_encode($push_n_config), true));
        }

        throw new Exception(__("Oops! Notification provider [$provider_name] configuration not found!"));
    }

    public function pusherConfig(array $credentials):array
    {
        $config = [
            "instanceId"    => $credentials['instance_id'],
            "secretKey"     => $credentials['primary_key']
        ];

        $this->provider_config = $config;

        // set provider
        $this->provider = new PushNotifications($config);

        return $config;
    }

    public function pusherSend() {
        $provider = $this->provider;

        $user_ids = $this->users;

        $publishable_ids = [];
        foreach($user_ids as $id) {
            $publishable_id = $this->make_publishable_id($id, $this->user_type);
            array_push($publishable_ids, $publishable_id);
        }

        // Debug log: What we're sending
        Log::info('Pusher: Preparing to send notification', [
            'user_ids' => $user_ids,
            'user_type' => $this->user_type,
            'publishable_ids' => $publishable_ids,
            'title' => $this->n_title,
            'body' => $this->n_desc,
        ]);

        try {
            $response = $provider->publishToUsers(
                $publishable_ids,
                [
                    "web"   => [
                        "notification"      => [
                            'title'     => $this->n_title,
                            'body'      => $this->n_desc,
                            'icon'      => $this->n_icon,
                        ],
                    ],
                    "fcm" => [
                        "notification" => [
                            'title'     => $this->n_title,
                            'body'      => $this->n_desc,
                            'icon'      => $this->n_icon,
                        ]
                    ],
                    "apns" => [
                        "aps" => [
                            "alert" => [
                                'title'     => $this->n_title,
                                'body'      => $this->n_desc,
                            ],
                            "sound" => "default",
                            "badge" => 1,
                        ]
                    ]
                ],
            );

            // Debug log: Response from Pusher
            Log::info('Pusher: Notification sent successfully', [
                'response' => $response,
                'publishable_ids' => $publishable_ids,
            ]);

            return $response;
        } catch (Exception $e) {
            // Debug log: Error details
            Log::error('Pusher: Failed to send notification', [
                'error' => $e->getMessage(),
                'publishable_ids' => $publishable_ids,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function make_publishable_id($user_id, $user_type):string
    {
        // Simple format: {user_type}-{user_id}
        return $user_type . "-" . $user_id;
    }

    public function unsubscribe()
    {
        $provider_name = $this->provider_name;

        if(array_key_exists($provider_name, $this->unsubscribeRegister())) {
            $method = $this->unsubscribeRegister()[$provider_name];
            return $this->$method();
        }

        throw new Exception(__("Oops! Notification unsubscribe method not declared"));
    }

    public function pusherUnsubscribe()
    {
        foreach($this->users as $user_id) {
            $this->provider->deleteUser($this->make_publishable_id($user_id, $this->user_type));
        }

        return true;
    }

}
