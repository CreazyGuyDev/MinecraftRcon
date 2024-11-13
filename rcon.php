<?php

class MinecraftRcon {
    private $socket;
    private $host;
    private $port;
    private $password;
    private $requestId;

    public function __construct($host, $port, $password) {
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
        $this->requestId = mt_rand(1, 1000);  // Random request ID for tracking responses
    }

    public function connect() {
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, 3);

        if (!$this->socket) {
            throw new Exception("Could not connect to RCON server: $errstr ($errno)");
        }

        if (!$this->authenticate()) {
            throw new Exception("Authentication failed.");
        }
    }

    public function authenticate() {
        $this->sendPacket(3, $this->password);  // 3 is the packet type for login
        $response = $this->receivePacket();

        return $response['requestId'] === $this->requestId;
    }

    public function sendCommand($command) {
        $this->sendPacket(2, $command);  // 2 is the packet type for command
        $response = $this->receivePacket();

        return $response['payload'];
    }

    public function disconnect() {
        fclose($this->socket);
    }

    private function sendPacket($type, $payload) {
        $packet = pack('VV', $this->requestId, $type) . $payload . "\x00\x00";
        $packet = pack('V', strlen($packet)) . $packet;
        fwrite($this->socket, $packet);
    }

    private function receivePacket() {
        $sizeData = fread($this->socket, 4);
        $size = unpack('V', $sizeData)[1];

        $data = fread($this->socket, $size);
        $response = unpack('VrequestId/Vtype/a*payload', $data);

        return $response;
    }
}
