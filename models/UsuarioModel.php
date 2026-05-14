<?php
require_once __DIR__ . '/../core/Ldap.php';

class UsuarioModel {

    private $ldap;

    public function __construct() {
        $this->ldap = new Ldap();
    }

    public function listar() {
        $attrs = array(
            'sAMAccountName', 'userPrincipalName', 'mail',
            'displayName', 'givenName', 'sn',
            'department', 'delegacion', 'extensionAttribute2',
            'employeeType', 'title',
        );

        $this->ldap->connect();
        $entries = $this->ldap->searchPaged(
            LDAP_BASE,
            '(&(objectClass=user))',
            $attrs
        );
        $this->ldap->disconnect();

        $results = array();
        foreach ($entries as $e) {
            $results[] = array(
                'sAMAccountName'      => isset($e['samaccountname'][0])      ? $e['samaccountname'][0]      : '',
                'userPrincipalName'   => isset($e['userprincipalname'][0])   ? $e['userprincipalname'][0]   : '',
                'mail'                => isset($e['mail'][0])                ? $e['mail'][0]                : '',
                'displayName'         => isset($e['displayname'][0])         ? $e['displayname'][0]         : '',
                'givenName'           => isset($e['givenname'][0])           ? $e['givenname'][0]           : '',
                'sn'                  => isset($e['sn'][0])                  ? $e['sn'][0]                  : '',
                'department'          => isset($e['department'][0])          ? $e['department'][0]          : '',
                'delegacion'          => isset($e['delegacion'][0])          ? $e['delegacion'][0]          : '',
                'extensionAttribute2' => isset($e['extensionattribute2'][0]) ? $e['extensionattribute2'][0] : '',
                'employeeType'        => isset($e['employeetype'][0])        ? $e['employeetype'][0]        : '',
                'title'               => isset($e['title'][0])               ? $e['title'][0]               : '',
            );
        }

        usort($results, function($a, $b) {
            return strcmp($a['displayName'], $b['displayName']);
        });

        return $results;
    }
}