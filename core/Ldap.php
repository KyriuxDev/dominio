<?php
class Ldap {

    private $ds = null;

    public function connect() {
        $this->ds = ldap_connect(LDAP_HOST);
        if (!$this->ds) {
            throw new Exception('No se pudo conectar al servidor LDAP.');
        }
        ldap_set_option($this->ds, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->ds, LDAP_OPT_REFERRALS, 0);

        if (!@ldap_bind($this->ds, LDAP_USER, LDAP_PASS)) {
            throw new Exception('Error de autenticacion LDAP: ' . ldap_error($this->ds));
        }
    }

    public function searchPaged($base, $filter, $attrs) {
        $results = array();
        $cookie  = '';

        do {
            ldap_control_paged_result($this->ds, PAGE_SIZE, true, $cookie);
            $sr = @ldap_search($this->ds, $base, $filter, $attrs, 0, 0, 30);
            if (!$sr) {
                throw new Exception('Error en la busqueda: ' . ldap_error($this->ds));
            }
            $entries = ldap_get_entries($this->ds, $sr);
            for ($i = 0; $i < $entries['count']; $i++) {
                $results[] = $entries[$i];
            }
            ldap_control_paged_result_response($this->ds, $sr, $cookie);
        } while ($cookie !== null && $cookie != '');

        return $results;
    }

    public function searchOne($base, $filter, $attrs) {
        $sr = @ldap_search($this->ds, $base, $filter, $attrs, 0, 1, 30);
        if (!$sr || ldap_count_entries($this->ds, $sr) === 0) {
            return null;
        }
        $entries = ldap_get_entries($this->ds, $sr);
        return $entries[0];
    }

    public function disconnect() {
        if ($this->ds) {
            ldap_unbind($this->ds);
            $this->ds = null;
        }
    }
}