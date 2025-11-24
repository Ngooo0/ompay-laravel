<?php

namespace App\Repository;

use App\Models\Client;

class ClientRepository
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function create(array $data): Client
    {
        return $this->client->create($data);
    }

    public function findById(int $id): ?Client
    {
        return $this->client->find($id);
    }

    public function findByPhone(string $phone): ?Client
    {
        return $this->client->where('phone', $phone)->first();
    }

    public function findByEmail(string $email): ?Client
    {
        return $this->client->where('email', $email)->first();
    }

    public function update(int $id, array $data): bool
    {
        $client = $this->findById($id);
        if ($client) {
            return (bool) $client->update($data);
        }
        return false;
    }

    public function delete(int $id): bool
    {
        $client = $this->findById($id);
        if ($client) {
            return (bool) $client->delete();
        }
        return false;
    }

    public function updateBalance(Client $client, int $balance): Client
    {
        $client->balance = $balance;
        $client->save();
        return $client;
    }
}
