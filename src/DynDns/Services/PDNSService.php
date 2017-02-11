<?php

namespace DynDns\Services;

use Silex\Application;

class PDNSService
{
    const ALLOWED_PARAMS = ['type', 'content', 'ttl'];
    const DEFAULT_TTL = 60;

    /**
     * @var Application
     */
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Resets the records for the given domain ID.
     *
     * Deletes all the records associated with the given domain ID and
     * inserts new records based on the data given in $data array.
     * Example array: [
     *      ['type' => 'A', 'content' => '123.34.156.78'],
     * ]
     *
     * @param $domainId int
     * @param $data array
     * @param $ttl int
     */
    public function updateRecord($domainId, array $data)
    {
        $domainId = (int)$domainId;

        $domainName = $this->app['db']->fetchColumn('SELECT `name` FROM domains WHERE id = ?', [$domainId], 0);
        if ($domainName === false) {
            throw new \RuntimeException("Domain ID not found.");
        }

        $this->app['db']->delete('records', ['domain_id' => $domainId]);

        foreach ($data as $row) {
            $rowFiltered = array_filter($row, function ($key) {
                return in_array($key, self::ALLOWED_PARAMS);
            }, ARRAY_FILTER_USE_KEY);

            $rowFiltered += [
                'change_date' => time(),
                'name' => $domainName,
                'auth' => 1,
                'disabled' => 0,
                'domain_id' => $domainId
            ];
            if (!array_key_exists('ttl', $rowFiltered)) {
                $rowFiltered['ttl'] = self::DEFAULT_TTL;
            }

            $rowFiltered['content'] = str_replace('%time%', time(), $rowFiltered['content']);
            $this->app['db']->insert('records', $rowFiltered);
        }
    }

    protected function updateSOA($currentSOA)
    {

    }
}