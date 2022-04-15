<?php

namespace App\Entity;

class Domain
{
    public string $id;
    public string $domain;
    public string $subdomain;
    public array $records = [];
}
