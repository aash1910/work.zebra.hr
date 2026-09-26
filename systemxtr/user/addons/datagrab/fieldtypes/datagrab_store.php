<?php

use BoldMinded\DataGrab\FieldTypes\AbstractFieldType;
use BoldMinded\DataGrab\FieldTypes\ImportField;
use BoldMinded\DataGrab\Service\Importer;
use Store\Model\Product;
use Store\Model\Stock;
use Store\Model\StockOption;

/**
 * DataGrab exp-resso Store fieldtype class
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 */
class Datagrab_store extends AbstractFieldType
{
    public function register_setting(string $fieldName): array
    {
        return [
            $fieldName => [
                'price',
                'sku',
                'width',
                'height',
                'length',
                'weight',
                'handling_surcharge',
                'free_shipping',
                'stock_level',
                'stock_limit',
                'min_order_qty',
                'modifiers',
                'stock',
                'generate_skus',
            ],
        ];
    }

    /*
    Example expected data

    {
        "title": "Product #1",
        "price": "100.00",
        "length": "20",
        "width": "10",
        "height": "5",
        "weight": "2",
        "handling": "3.00",
        "free_shipping": "1",
        "modifiers": [
          {
            "type": "var",
            "name": "Small",
            "instructions": "Foobar",
            "options": [
              {
                "name": "cyan",
                "price": "-11.00"
              },
              {
                "name": "magenta",
                "price": "-21.00"
              }
            ]
          },
          {
            "type": "var",
            "name": "Medium",
            "instructions": "Fizzbazz",
            "options": [
              {
                "name": "yellow",
                "price": "+5.00"
              },
              {
                "name": "black",
                "price": "+10.00"
              }
            ]
          }
        ],
        "stock": [
          {
            "sku": "cyan-yellow",
            "track_stock": "0",
            "stock_level": "30",
            "min_order_qty": "1"
          },
          {
            "sku": "cyan-black",
            "track_stock": "0",
            "stock_level": "20",
            "min_order_qty": "2"
          },
          {
            "sku": "magenta-yellow",
            "track_stock": "0",
            "stock_level": "10",
            "min_order_qty": "3"
          },
          {
            "sku": "magenta-black",
            "track_stock": "0",
            "stock_level": "5",
            "min_order_qty": "4"
          }
        ]
      }

     */

    public function display_configuration(
        Importer $importer,
        string $fieldName,
        string $fieldLabel,
        string $fieldType,
        bool $fieldRequired = false,
        array $data = []
    ): array
    {
        $config = [];
        $config['label'] = $this->displayLabel($fieldLabel, $fieldName, $fieldRequired, 'store');
        $fieldSettings = $data['field_settings'][$fieldName] ?? [];

        $fieldSets = ee('View')
            ->make('ee:_shared/form/section')
            ->render([
                'name' => 'fieldset_group',
                'settings' => $this->getFormFields(
                    $fieldName,
                    $fieldSettings,
                    $data,
                    $this->getSavedFieldValues($data, $fieldName),
                )
            ]);

        $config['value'] = $fieldSets;

        return $config;
    }

    public function getFormFields(
        string $fieldName,
        array $fieldSettings,
        array $data = [],
        array $savedFieldValues = [],
        string $contentType = 'channel',
    ): array
    {
        $fieldOptions[] = [
            'title' => 'Price',
            'desc' => '',
            'fields' => [
                $fieldName . '[price][value]' => [
                    'type' => 'dropdown',
                    'choices' => $data['data_fields'],
                    'value' => $savedFieldValues['price']['value'] ?? '',
                ]
            ]
        ];

$fieldOptions[] = [
    'title' => 'SKU',
    'desc' => '',
    'fields' => [
        $fieldName . '[sku][value]' => [
            'type' => 'dropdown',
            'choices' => $data['data_fields'],
            'value' => $savedFieldValues['sku']['value'] ?? '',
        ]
    ]
];

        $fieldOptions[] = [
            'title' => 'Weight',
            'desc' => '',
            'fields' => [
                $fieldName . '[weight][value]' => [
                    'type' => 'dropdown',
                    'choices' => $data['data_fields'],
                    'value' => $savedFieldValues['weight']['value'] ?? '',
                ]
            ]
        ];

        $fieldOptions[] = [
            'title' => 'Width',
            'desc' => '',
            'fields' => [
                $fieldName . '[width][value]' => [
                    'type' => 'dropdown',
                    'choices' => $data['data_fields'],
                    'value' => $savedFieldValues['width']['value'] ?? '',
                ]
            ]
        ];

        $fieldOptions[] = [
            'title' => 'Length',
            'desc' => '',
            'fields' => [
                $fieldName . '[length][value]' => [
                    'type' => 'dropdown',
                    'choices' => $data['data_fields'],
                    'value' => $savedFieldValues['length']['value'] ?? '',
                ]
            ]
        ];

        $fieldOptions[] = [
            'title' => 'Height',
            'desc' => '',
            'fields' => [
                $fieldName . '[height][value]' => [
                    'type' => 'dropdown',
                    'choices' => $data['data_fields'],
                    'value' => $savedFieldValues['height']['value'] ?? '',
                ]
            ]
        ];

        $fieldOptions[] = [
            'title' => 'Handling surcharge',
            'desc' => '',
            'fields' => [
                $fieldName . '[handling_surcharge][value]' => [
                    'type' => 'dropdown',
                    'choices' => $data['data_fields'],
                    'value' => $savedFieldValues['handling_surcharge']['value'] ?? '',
                ]
            ]
        ];

        $fieldOptions[] = [
            'title' => 'Free shipping',
            'desc' => '',
            'fields' => [
                $fieldName . '[free_shipping][value]' => [
                    'type' => 'dropdown',
                    'choices' => $data['data_fields'],
                    'value' => $savedFieldValues['free_shipping']['value'] ?? '',
                ]
            ]
        ];

        $fieldOptions[] = [
            'title' => 'Modifiers',
            'desc' => '',
            'fields' => [
                $fieldName . '[modifiers][value]' => [
                    'type' => 'dropdown',
                    'choices' => $data['data_fields'],
                    'value' => $savedFieldValues['modifiers']['value'] ?? '',
                ]
            ]
        ];

        $fieldOptions[] = [
            'title' => 'Stock',
            'desc' => '',
            'fields' => [
                $fieldName . '[stock][value]' => [
                    'type' => 'dropdown',
                    'choices' => $data['data_fields'],
                    'value' => $savedFieldValues['stock']['value'] ?? '',
                ]
            ]
        ];

        $fieldOptions[] = [
            'title' => 'Auto-Generate SKUs',
            'desc' => 'If no stock is defined should DataGrab auto-generate SKUs based on modifier option names?',
            'fields' => [
                $fieldName . '[generate_skus][value]' => [
                    'type' => 'toggle',
                    'choices' => $data['data_fields'],
                    'value' => $savedFieldValues['generate_skus']['value'] ?? 'n',
                ]
            ]
        ];

        $fieldOptions[] = [
            'title' => 'Reset Stock',
            'desc' => 'Should DataGrab reset stock if updating an existing product? This will delete all 
                information from the Stock table on the product and reset it to the given values in the import file.
                If you want to update product data, but keep existing stock levels, leave this disabled.',
            'fields' => [
                $fieldName . '[reset_stock][value]' => [
                    'type' => 'toggle',
                    'choices' => $data['data_fields'],
                    'value' => $savedFieldValues['reset_stock']['value'] ?? 'n',
                ]
            ]
        ];

        return $fieldOptions;
    }

    public function hasFieldValue(array $settings = []): string
    {
        foreach ($settings as $setting) {
            if (isset($setting['value']) && $setting['value'] !== '') {
                // Doesn't really matter which, so get the first
                return $setting['value'];
            }
        }

        return '';
    }


    public function finalPostData(ImportField $importField): array
    {
        $config = $importField->fieldImportConfig;
    
        $stockField = $config['stock']['value'] ?? '';
        $skuField   = $config['sku']['value'] ?? '';
    
        $price = $config['price']['value'] ?? '';
        $weight = $config['weight']['value'] ?? '';
        $width = $config['width']['value'] ?? '';
        $length = $config['length']['value'] ?? '';
        $height = $config['height']['value'] ?? '';
        $handlingSurcharge = $config['handling_surcharge']['value'] ?? '';
        $modifiers = $config['modifiers']['value'] ?? '';
        $resetStock = get_bool_from_string($config['reset_stock']['value'] ?? '');
        $generateSkus = get_bool_from_string($config['generate_skus']['value'] ?? '');
        $freeShipping = $config['free_shipping']['value'] ?? '';
    
        $data = [];
    
        // -----------------------------
        // IMPORTED STOCK / SKU VALUES
        // -----------------------------
        $importedStockLevel = null;
        if ($stockField) {
            $importedStockLevel = $this->toNumber(
                $importField->importer->dataType->get_item($importField->importItem, $stockField)
            );
        }
    
        $importedSku = null;
        if ($skuField) {
            $importedSku = trim(
                $importField->importer->dataType->get_item($importField->importItem, $skuField)
            );
        }
    
        // -----------------------------
        // OTHER FIELD VALUES
        // -----------------------------
        if ($price) {
            $rawPrice = $importField->importer->dataType->get_item($importField->importItem, $price);
            $data['price'] = $rawPrice;
        }
    
        if ($width) {
            $data['width'] = $this->toNumber($importField->importer->dataType->get_item($importField->importItem, $width));
        }
        if ($height) {
            $data['height'] = $this->toNumber($importField->importer->dataType->get_item($importField->importItem, $height));
        }
        if ($length) {
            $data['length'] = $this->toNumber($importField->importer->dataType->get_item($importField->importItem, $length));
        }
        if ($weight) {
            $data['weight'] = $this->toNumber($importField->importer->dataType->get_item($importField->importItem, $weight));
        }
        if ($handlingSurcharge) {
            $data['handling'] = $this->toNumber($importField->importer->dataType->get_item($importField->importItem, $handlingSurcharge));
        }
        if ($freeShipping) {
            $data['free_shipping'] = $this->toNumber($importField->importer->dataType->get_item($importField->importItem, $freeShipping));
        }
    
        // -----------------------------
        // RESET STOCK (if enabled)
        // -----------------------------
        $alreadyUpdated = !in_array($importField->entryId, $importField->importer->entries);
        if ($importField->entryId && $alreadyUpdated && $resetStock) {
            ee()->db->where('entry_id', $importField->entryId);
            ee()->db->delete('exp_store_stock');
        }
    
        // -----------------------------
        // FETCH EXISTING STOCK
        // -----------------------------
        ee()->db->select('*');
        ee()->db->where('entry_id', $importField->entryId);
        $query = ee()->db->get('exp_store_stock');
    
        $data['stock'] = [];
        $count = 0;
    
        if ($query->num_rows()) {
            foreach ($query->result_array() as $row) {
                $stockRow = [
                    'id'            => $row['id'],
                    'sku'           => $row['sku'],
                    'stock_level'   => $row['stock_level'],
                    'track_stock'   => $row['track_stock'],
                    'min_order_qty' => $row['min_order_qty'],
                ];
    
                // Override with imported values if present
                if ($importedSku !== null && $importedSku !== '') {
                    $stockRow['sku'] = $importedSku;
                }
                if ($importedStockLevel !== null) {
                    $stockRow['stock_level'] = $importedStockLevel;
                    $stockRow['track_stock'] = 1;
                }
    
                $data['stock'][] = $stockRow;
                $count++;
            }
        } else {
            // New entry → create base stock row
            $data['stock'][] = [
                'sku'           => $importedSku ?? '',
                'stock_level'   => $importedStockLevel ?? 0,
                'track_stock'   => $importedStockLevel !== null ? 1 : 0,
                'min_order_qty' => 1,
            ];
        }
    
        // -----------------------------
        // POST TO STORE
        // -----------------------------
        $_POST['store_product_field'] = $data;
    
        //ee()->logger->developer("data: " . var_export($data, true));
    
        return $data;
    }


    private function toNumber($number)
    {
        return preg_replace('/([^0-9\\.])/i', '', $number);
    }

    public function rebuild_post_data(
        Importer $importer,
        int $fieldId = 0,
        array &$data = [],
        array $entryData = []
    ) {
        // Version 2
        $data['field_id_' . $fieldId] = 'store';
        $_POST['store_product_field'] = array(
            'price' => '',
            'stock' => array(
                array(
                    'sku' => '',
                    'min_order_qty' => ''
                )
            ),
            'weight' => '',
            'length' => '',
            'width' => '',
            'height' => '',
            'handling' => '',
            'free_shipping' => ''
        );

        ee()->db->from('exp_store_products');
        ee()->db->join('exp_store_stock', 'exp_store_products.entry_id = exp_store_stock.entry_id');
        ee()->db->where('exp_store_products.entry_id', $entryData['entry_id']);

        $query = ee()->db->get();

        if ($query->num_rows() > 0) {
            $row = $query->row_array();

            $_POST['store_product_field'] = array(
                'price' => '',
                'stock' => array(
                    array(
                        'sku' => '',
                        'min_order_qty' => ''
                    )
                ),
                'weight' => '',
                'length' => '',
                'width' => '',
                'height' => '',
                'handling' => '',
                'free_shipping' => ''
            );

            $_POST['store_product_field'] = array(
                'price' => $row['price'],
                'stock' => array(
                    array(
                        'sku' => $row['sku'],
                        'min_order_qty' => $row['min_order_qty'],
                        'track_stock' => $row['track_stock'],
                        'stock_level' => $row['stock_level']
                    )
                ),
                'weight' => $row['weight'],
                'length' => $row['length'],
                'width' => $row['width'],
                'height' => $row['height'],
                'handling' => $row['handling'],
                'free_shipping' => $row['free_shipping']
            );

        }
    }
}
