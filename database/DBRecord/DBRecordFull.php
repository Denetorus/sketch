<?php

namespace sketch\database\DBRecord;

abstract class DBRecordFull extends DBRecordBase
{

    public function getFields(): array
    {
        return [];
    }

    public function getJoinsText(): string
    {
        return '';
    }

    public function load(): void
    {
        $list = $this->getListWithExtension(
            [],
            [(object)['field' => $this->key_name, 'type' => '=', 'value' => $this->ref]]
        );
        $this->props = (count($list) ? $list[0] : []);
    }

    public function getListWithExtension($gottenSorts = [], $gottenFilters = []): array
    {

        $fields = $this->getFields();
        $query_text = $this->getQueryTextWithExtension($fields);

        $filters = $this->getFiltersByGottenFilters($gottenFilters);
        $filter_data = $this->getFiltersParameters($filters);
        if ($filter_data->text) {
            $query_text .= " WHERE " . $filter_data->text;
        }

        $sorts = $this->getSortsByGottenSorts($gottenSorts);
        $sort_text = $this->getSortsQueryText($sorts);
        if ($sort_text) {
            $query_text .= " ORDER BY " . $sort_text;
        }

        $query_result = $this->db->select($query_text, $filter_data->params);

        return $this->prepareQueryResultByFields($query_result, $fields);

    }


    public function getQueryTextWithExtension($fields): string
    {

        $selected_params = '';
        $join_tables = '';
        $count_joined_tables = 0;
        $sign = '';
        foreach ($fields as $field) {
            $tn = $field['table_name'] ?? 'list';
            $cn = $field['column_name'] ?? $field['name'];
            $selected_params .= $sign . $tn . '.' . $cn . ' as ' . $field['name'];
            $sign = ',';
            if (isset($field['refTable'])) {
                $count_joined_tables++;
                $join_table_name = 'JT' . $count_joined_tables;
                $selected_params .= $sign . $join_table_name . '.description as _' . $field['name'] . '_presentation';
                $join_tables .= " LEFT JOIN "
                    . $field['refTable'] . " as " . $join_table_name
                    . ' on ' . $tn . '.' . $cn . '=' . $join_table_name . '.id';
            }
        }

        return "SELECT $selected_params FROM $this->table_name as list " . $join_tables . " " . $this->getJoinsText();

    }


    // sorts

    public function getSortsByGottenSorts($gottenSorts): array
    {
        $result = [];
        $correct_sorts = $this->getCorrectSorts();
        foreach ($gottenSorts as $sort) {
            if (strpos($correct_sorts, "," . $sort . ",") !== false) {
                $result[] = "list." . $sort;
            }
        }
        return $result;
    }

    public function getCorrectSorts(): string
    {
        $result = "";
        $fields = $this->getFields();
        foreach ($fields as $field) {
            $result .= "," . $field['name'] . " desc," . $field['name'] . " asc,";
        }
        return $result;

    }

    public function getSortsQueryText($sorts): string
    {
        $sort_text = "";
        $is_first = true;
        foreach ($sorts as $sort) {

            if ($is_first) {
                $sort_text = $sort;
                $is_first = false;
            } else {
                $sort_text .= "," . $sort;
            }

        }
        return $sort_text;

    }


    // filters

    public function getFiltersByGottenFilters($gottenFilters): array
    {

        $correct_fields = $this->getCorrectFilterFields();
        $correct_types = $this->getCorrectFilterTypes();

        $result = [];
        foreach ($gottenFilters as $filter) {
            if (strpos($correct_fields, "," . $filter->field . ",") === false)
                continue;
            if (strpos($correct_types, "," . $filter->type . ",") === false)
                continue;
            $result[] = [
                'field' => $filter->field,
                'type' => $filter->type,
                'value' => $filter->value
            ];
        }

        return $result;

    }

    public function getCorrectFilterTypes(): string
    {
        return ',=,!=,>,<,>=,<=,like,ilike,not like,not ilike,';
    }

    public function getCorrectFilterFields(): string
    {
        $result = "";
        $fields = $this->getFields();
        foreach ($fields as $field) {
            $result .= "," . $field['name'];
        }
        return $result;
    }

    /**
     * @param $filters
     * @return array{ text: string,  params: array}
     */
    public function getFiltersParameters($filters): array
    {
        $query_params = [];
        $filter_text = "";
        $is_first = true;
        $params_number = 0;
        foreach ($filters as $filter) {

            if ($is_first) {
                $is_first = false;
            } else {
                $filter_text .= ",";
            }

            $params_number += 1;
            $field_full = strpos($filter['field'], '.') !== false ? $filter['field'] : "list." . $filter['field'];
            $filter_text .= $field_full . " " . $filter['type'] . ' :param' . $params_number;
            if ($filter['type'] === 'like') {
                $query_params['param' . $params_number] = '%' . $filter['value'] . '%';
            } else {
                $query_params['param' . $params_number] = $filter['value'];
            }

        }

        return [
            'text' => $filter_text,
            'params' => $query_params
        ];
    }


    // results

    public function prepareQueryResultByFields($query_result, $fields): array
    {

        foreach ($fields as $field) {

            if (!isset($field['refTable']))
                continue;

            foreach ($query_result as &$row) {
                $row[$field['name']] = [
                    'ref' => $row[$field['name']],
                    'refTable' => $field['refTable'],
                    'presentation' => $row['_' . $field['name'] . '_presentation']
                ];
                unset($row['_' . $field['name'] . '_presentation']);
            }

        }

        return $query_result;

    }

}