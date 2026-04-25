<?php
namespace MyApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

require_once __DIR__ . '/../config.php'; // Access to DB

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $users; // Map conn_id to user info

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->users = [];
        echo "Chat Server Started...\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        // Evaluate query string to identify user
        $querystring = $conn->httpRequest->getUri()->getQuery();
        parse_str($querystring, $query);
        
        if (isset($query['user_id'])) {
            $this->clients->attach($conn);
            $this->users[$conn->resourceId] = [
                'id' => $query['user_id'],
                'role' => isset($query['role']) ? $query['role'] : 'user',
                'conn' => $conn
            ];
            echo "New connection! ({$conn->resourceId}) User: {$query['user_id']}\n";
            
            // Broadcast User Online Status
            $this->broadcastUserStatus($query['user_id'], 'online');
        } else {
            $conn->close();
        }
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients) - 1;
        $active_user = $this->users[$from->resourceId];
        
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

        $data = json_decode($msg, true);
        
        if (!$data) return;

        switch ($data['type']) {
            case 'chat':
                $this->handleChatMessage($from, $data, $active_user);
                break;
            case 'typing':
                $this->handleTyping($from, $data, $active_user);
                break;
        }
    }

    private function handleChatMessage($from, $data, $sender) {
        // Save to DB (using global $conn from config.php or new connection)
        global $servername, $username, $password, $dbname;
        $db = new \mysqli($servername, $username, $password, $dbname);
        
        $sender_id = $sender['id'];
        $recipient_id = $data['recipient_id'];
        $message = $db->real_escape_string($data['message']);
        $attachment = isset($data['attachment']) ? $db->real_escape_string($data['attachment']) : null;
        $msg_type = $attachment ? 'file' : 'text';

        $sql = "INSERT INTO messages (sender_id, receiver_id, message, attachment_path, type, created_at) 
                VALUES ('$sender_id', '$recipient_id', '$message', '$attachment', '$msg_type', NOW())";
        
        if ($db->query($sql)) {
            $msg_id = $db->insert_id;
            
            $payload = json_encode([
                'type' => 'chat',
                'id' => $msg_id,
                'sender_id' => $sender_id,
                'message' => $message,
                'attachment' => $attachment,
                'created_at' => date('Y-m-d H:i:s'),
                'is_sender' => false
            ]);

            // Send to Recipient if online
            foreach ($this->users as $user) {
                if ($user['id'] == $recipient_id) {
                    $user['conn']->send($payload);
                }
            }
            
            // Ack to Sender
            $from->send(json_encode([
                'type' => 'chat_ack',
                'id' => $msg_id,
                'temp_id' => isset($data['temp_id']) ? $data['temp_id'] : null,
                'created_at' => date('Y-m-d H:i:s')
            ]));
        }
        $db->close();
    }

    private function handleTyping($from, $data, $sender) {
        $recipient_id = $data['recipient_id'];
        $payload = json_encode([
            'type' => 'typing',
            'sender_id' => $sender['id'],
            'is_typing' => $data['is_typing']
        ]);

        foreach ($this->users as $user) {
            if ($user['id'] == $recipient_id) {
                $user['conn']->send($payload);
            }
        }
    }

    private function broadcastUserStatus($user_id, $status) {
        $payload = json_encode([
            'type' => 'status',
            'user_id' => $user_id,
            'status' => $status
        ]);
        
        foreach ($this->clients as $client) {
            $client->send($payload);
        }
    }

    public function onClose(ConnectionInterface $conn) {
        if (isset($this->users[$conn->resourceId])) {
            $user_id = $this->users[$conn->resourceId]['id'];
            unset($this->users[$conn->resourceId]);
            $this->broadcastUserStatus($user_id, 'offline');
            echo "Connection {$conn->resourceId} has disconnected\n";
        }
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}
