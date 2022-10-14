<?php

class Mappature extends CI_Model
{


    public function getMappature()
    {
        $mappature_data = $this->apilib->search('movimenti_mappature');
        return array_key_value_map($mappature_data, 'movimenti_mappature_key_value', 'movimenti_mappature_value');
    }
}
