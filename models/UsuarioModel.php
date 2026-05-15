<?php
require_once __DIR__ . '/../core/Ldap.php';

class UsuarioModel {

    private $ldap;

    public function __construct() {
        $this->ldap = new Ldap();
    }

    public function buscar($sam) {
        $attrs = array(
            'sAMAccountName', 'displayName', 'givenName', 'sn',
            'description', 'info', 'title', 'department', 'company',
            'co', 'countryCode', 'employeeID', 'employeeType', 'division',
            'mail', 'userPrincipalName', 'telephoneNumber', 'otherTelephone',
            'physicalDeliveryOfficeName', 'streetAddress', 'postalCode', 'l', 'st',
            'delegacion', 'extensionAttribute2',
            // Cuenta
            'userAccountControl', 'accountExpires', 'pwdLastSet',
            'badPwdCount', 'badPasswordTime', 'lockoutTime',
            'lastLogon', 'lastLogonTimestamp', 'logonCount',
            'logonHours', 'userWorkstations', 'scriptPath',
            'profilePath', 'homeDirectory',
            // Grupos
            'memberOf',
            // Contraseña expira
            'msDS-UserPasswordExpiryTimeComputed',
            // Foto
            'thumbnailPhoto',
        );

        $filter = '(&(objectClass=user)(sAMAccountName=' . ldap_escape($sam, '', LDAP_ESCAPE_FILTER) . '))';

        $this->ldap->connect();
        $entry = $this->ldap->searchOne('DC=sur,DC=imss,DC=gob,DC=mx', $filter, $attrs);
        $this->ldap->disconnect();

        return $entry;
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

    public function exportar() {
        $attrs = array(
            'sAMAccountName', 'userPrincipalName', 'mail',
            'displayName', 'givenName', 'sn', 'cn', 'name',
            'title', 'description', 'info',
            'department', 'company', 'employeeType', 'employeeID', 'division',
            'delegacion', 'coordinacion', 'calle', 'colonia', 'municipio',
            'extensionAttribute1', 'extensionAttribute2', 'extensionAttribute3', 'extensionAttribute4',
            'telephoneNumber', 'otherTelephone', 'otherIpPhone', 'otherMobile', 'otherPager',
            'physicalDeliveryOfficeName', 'streetAddress', 'postalCode', 'l', 'st', 'co', 'c', 'countryCode',
            'userAccountControl', 'accountExpires', 'pwdLastSet', 'lockoutTime',
            'badPwdCount', 'badPasswordTime', 'logonCount',
            'lastLogon', 'lastLogonTimestamp', 'whenCreated', 'whenChanged',
            'uSNCreated', 'uSNChanged', 'codePage', 'primaryGroupID',
            'mailNickname', 'targetAddress', 'legacyExchangeDN',
            'msExchVersion', 'msExchRecipientTypeDetails', 'msExchRemoteRecipientType',
            'msExchMobileMailboxFlags', 'msExchRecipLimit', 'msExchWhenMailboxCreated',
            'msExchALObjectVersion', 'msExchUserAccountControl', 'msExchTextMessagingState',
            'msDS-ExternalDirectoryObjectId',
            'msRTCSIP-PrimaryUserAddress', 'msRTCSIP-UserEnabled',
            'msRTCSIP-FederationEnabled', 'msRTCSIP-InternetAccessEnabled',
            'msRTCSIP-OptionFlags', 'msRTCSIP-DeploymentLocator',
            'memberOf',
            'proxyAddresses',
            'showInAddressBook',
            'objectCategory', 'objectClass',
            'instanceType', 'sAMAccountType',
            'scriptPath', 'profilePath', 'homeDirectory',
            'userWorkstations',
        );

        $this->ldap->connect();
        $entries = $this->ldap->searchPaged(
            LDAP_BASE,
            '(&(objectClass=user))',
            $attrs
        );
        $this->ldap->disconnect();

        // Construir encabezados dinámicos desde todos los atributos que llegaron
        $allKeys = array();
        foreach ($entries as $e) {
            foreach ($e as $k => $v) {
                if (is_int($k) || $k === 'count' || $k === 'dn') continue;
                if (!in_array($k, $allKeys)) $allKeys[] = $k;
            }
        }
        sort($allKeys);

        $rows = array();
        foreach ($entries as $e) {
            $row = array('dn' => isset($e['dn']) ? $e['dn'] : '');
            foreach ($allKeys as $k) {
                if (!isset($e[$k]) || !isset($e[$k]['count'])) {
                    $row[$k] = '';
                    continue;
                }
                // Multivalor: unir con |
                $vals = array();
                for ($i = 0; $i < $e[$k]['count']; $i++) {
                    $vals[] = $e[$k][$i];
                }
                // Convertir timestamps conocidos
                if (in_array($k, array('whencreated','whenchanged'))) {
                    $vals = array_map(array($this, 'ldapTs'), $vals);
                }
                if (in_array($k, array('pwdlastset','lastlogon','lastlogontimestamp',
                                    'accountexpires','badpasswordtime','lockouttime'))) {
                    $vals = array_map(array($this, 'winTs'), $vals);
                }
                $row[$k] = implode(' | ', $vals);
            }
            $rows[] = $row;
        }

        usort($rows, function($a, $b) {
            return strcmp(
                isset($a['displayname']) ? $a['displayname'] : '',
                isset($b['displayname']) ? $b['displayname'] : ''
            );
        });

        return array('headers' => array_merge(array('dn'), $allKeys), 'rows' => $rows);
    }

    private function winTs($val) {
        if (!$val || $val == '0' || $val == '9223372036854775807') return 'Nunca';
        $unix = round(((float)$val - 116444736000000000) / 10000000);
        if ($unix <= 0) return 'Nunca';
        $unix += TZ_OFFSET * 3600;
        return date('d/m/Y H:i', $unix);
    }

    private function ldapTs($val) {
        if (!$val) return '';
        // Formato: 20260513192233.0Z
        if (preg_match('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', $val, $m)) {
            $unix = gmmktime($m[4], $m[5], $m[6], $m[2], $m[3], $m[1]);
            $unix += TZ_OFFSET * 3600;
            return date('d/m/Y H:i', $unix);
        }
        return $val;
    }
}