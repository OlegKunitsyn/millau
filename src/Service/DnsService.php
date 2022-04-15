<?php

namespace App\Service;

use Symfony\Component\Mime\Address;

class DnsService
{
    private const PREFIX = 'millau-';
    private CryptService $crypt;

    public function __construct(CryptService $crypt)
    {
        $this->crypt = $crypt;
    }

    /**
     * @param Address[] $addresses
     */
    public function getGroups(array $addresses): array
    {
        $groups = [];
        foreach ($addresses as $address) {
            $parts = explode('@', $address->getAddress());
            $tld = array_pop($parts);

            if ($group = $this->getGroup($tld)) {
                $groups[] = $group;
                break;
            }
        }
        return $groups;
    }

    public function getGroup(string $tld): ?string
    {
        $records = dns_get_record($tld, DNS_TXT);
        foreach ($records as $record) {
            if (0 === strpos($record['txt'], self::PREFIX)) {
                return $this->crypt->decrypt(trim(str_replace(self::PREFIX, '', $record['txt'])), $tld);
            }
        }
        return null;
    }

    public function getGroupData(string $group, string $tld): string
    {
        return self::PREFIX . $this->crypt->encrypt($group, $tld);
    }
}
