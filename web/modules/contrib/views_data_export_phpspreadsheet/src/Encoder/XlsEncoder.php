<?php

namespace Drupal\views_data_export_phpspreadsheet\Encoder;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Drupal\csv_serialization\Encoder\CsvEncoder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Drupal\Component\Utility\Html;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Adds Xls encoder support for the Serialization API.
 */
class XlsEncoder implements EncoderInterface, DecoderInterface {

  /**
   * The format that this encoder supports.
   *
   * @var string
   */
  protected static $supportFormat = ['xls', 'xlsx', 'ods', 'gnumeric'];

  /**
   * Format extenstion.
   *
   * @var string
   */
  public string $format = 'xlsx';

  /**
   * The root directory of the Drupal installation.
   *
   * @var string
   */
  protected $root;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs an XLS encoder.
   */
  public function __construct(string $app_root, Token $token, ConfigFactoryInterface $config_factory) {
    $this->root = $app_root;
    $this->token = $token;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding($format, $context = FALSE): bool {
    return in_array($format, static::$supportFormat);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDecoding($format, $context = FALSE): bool {
    return in_array($format, static::$supportFormat);
  }

  /**
   * {@inheritdoc}
   */
  public function decode($data, $format, array $context = []) {
    $reader = IOFactory::createReader(ucfirst($this->format));
    $spreadsheet = $reader->loadFromString($data);
    $sheetIndicators = $spreadsheet->getActiveSheet();
    ;
    $sheetData = NULL;
    if (NULL !== $sheetIndicators) {
      $sheetData = $sheetIndicators->toArray(NULL, TRUE, TRUE, TRUE);
    }
    return $sheetData;
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = []): string {
    switch (gettype($data)) {
      case 'array':
        break;

      case 'object':
        $data = (array) $data;
        break;

      default:
        $data = [$data];
        break;
    }
    $header = FALSE;
    $footer = FALSE;
    $fields = NULL;
    $colors = NULL;
    $autoSize = NULL;
    $title = '';
    $totalCol = 0;
    $fieldTypes = [];
    if (!empty($context["views_style_plugin"]) && !empty($options = $context["views_style_plugin"]->options)) {
      if (!empty($options["formats"])) {
        $this->format = end($options["formats"]);
      }
      $view = $context["views_style_plugin"]->view;
      if (!empty($view->preview)) {
        $csvEncoder = new CsvEncoder();
        return $csvEncoder->encode($data, $format, $context);
      }
      $total_rows = $view->total_rows;
      $limit = $view->query->getLimit();

      $token = $this->token;
      $configFactory = $this->configFactory;
      foreach ($view->field as $fieldName => $field) {
        if ($field->options['exclude']) {
          continue;
        }
        $fields[$fieldName] = !empty($field->options['label']) ? $field->options['label'] : (string) $field->definition['title'];
        if (method_exists($field, 'getCacheTags')) {
          foreach ($field->getCacheTags() as $tag) {
            if (!empty($tag)) {
              $extract = explode(':', $tag);
              $configField = $configFactory->getEditable($extract[1]);
              $fieldTypes[$fieldName] = $configField->getRawData()["type"];
              break;
            }
          }
        }
      }
      $totalCol = count($fields);

      if (empty($offset = $view->getOffset())) {
        $index = 1;
        // Set color background to the column.
        foreach ($fields as $fieldName => $field) {
          $col_name = Coordinate::stringFromColumnIndex($index);
          if (!empty($options["xls_settings"]["color"][$fieldName])) {
            $colorSet = ltrim($options["xls_settings"]["color"][$fieldName], '#');
            if (!in_array($colorSet, ['000', '000000'])) {
              // . '1' . $col_name . $total_rows;
              $col_coordinate = $col_name;
              $colors[$col_coordinate] = $colorSet;
            }
          }
          // Set column auto size.
          $autoSize[$col_name] = TRUE;
          $index++;
        }
        // Add column header.
        if (!empty($fields)) {
          array_unshift($data, $fields);
        }
        // Add print header.
        if (!empty($options["xls_settings"]["header"]["value"])) {
          $header = $view->getStyle()->tokenizeValue($options["xls_settings"]["header"]["value"], 0);
          $header = $token->replace($header, ['view' => $view]);
          array_unshift($data, [$header]);
        }
        // Add print footer.
        if (!empty($options["xls_settings"]["footer"]["value"])) {
          $footer = $view->getStyle()->tokenizeValue($options["xls_settings"]["footer"]["value"], 0);
          $footer = $token->replace($footer, ['view' => $view]);
          if (empty($limit)) {
            $data[] = [$footer];
          }
        }
        // Set print title.
        if (!empty($view->getTitle())) {
          $title = $view->getTitle();
        }
      }
      // Add footer end of file.
      if (!empty($limit) && !empty($options["xls_settings"]["footer"]["value"]) && $total_rows < $limit) {
        $footer = $view->getStyle()->tokenizeValue($options["xls_settings"]["footer"]["value"], 0);
        $footer = $token->replace($footer, ['view' => $view]);
        $data[] = [$footer];
      }
    }
    try {
      // Create a new excel object.
      $spreadsheet = $this->createSpreadsheet();
      $worksheet = $spreadsheet->getActiveSheet();
      // Set worksheet name.
      if (!empty($title)) {
        $title = preg_replace('/[^\da-z ]/i', '', trim($title));
        $title = substr($title, 0, 31);
        $worksheet->setTitle($title);
      }
      $bold = [
        'alignment' => [
          'vertical' => Alignment::VERTICAL_CENTER,
          'horizontal' => Alignment::HORIZONTAL_CENTER_CONTINUOUS,
        ],
        'font' => ['bold' => TRUE],
      ];
      // Print header.
      if ($header) {
        $worksheet->setCellValue('A1', $header);
        $worksheet->getHeaderFooter()->setOddHeader('&C&H' . $header);
        $worksheet->getStyle('A1')->applyFromArray($bold);
        $worksheet->mergeCellsByColumnAndRow(1, 1, $totalCol, 1);
      }
      // Print footer with pagination.
      if ($footer) {
        $worksheet->getHeaderFooter()->setOddFooter('&L&B' . $footer . '&RPage &P of &N');
      }
      // Set auto size.
      if ($autoSize) {
        foreach ($autoSize as $coordinate => $active) {
          $worksheet->getColumnDimension($coordinate)->setAutoSize($active);
        }
        $rowBold = 1;
        if ($header) {
          $rowBold = 2;
        }
        $worksheet->getStyle('A' . $rowBold . ':' . $coordinate . $rowBold)->applyFromArray($bold);
      }
      // Set document properties.
      if (!empty($options['xls_settings']['metadata']) && empty($offset)) {
        $spreadsheet->getProperties()
          ->setCreator($options['xls_settings']['metadata']['creator'])
          ->setLastModifiedBy($options['xls_settings']['metadata']['last_modified_by'])
          ->setTitle($options['xls_settings']['metadata']['title'])
          ->setSubject($options['xls_settings']['metadata']['subject'])
          ->setDescription($options['xls_settings']['metadata']['description'])
          ->setKeywords($options['xls_settings']['metadata']['keywords'])
          ->setCategory($options['xls_settings']['metadata']['category'])
          ->setManager($options['xls_settings']['metadata']['manager'])
          ->setCompany($options['xls_settings']['metadata']['company']);
      }
      // Check is href and text.
      $a_tag_pattern = '/<a\s+[^>]*href="([^"]+)".*(.*?)<\/a>/i';
      // Check is url.
      $url_pattern = '/^(https:\/\/|http:\/\/)/i';
      // Generate data.
      foreach ($data as $line => $rowData) {
        $column = 1;
        $row = $worksheet->getHighestRow();
        if (!$offset && !$line) {
          $row = 0;
        }
        foreach ($rowData as $fieldName => $value) {
          if (!empty($fieldTypes[$fieldName]) && $fieldTypes[$fieldName] == 'image' && !empty($value)) {
            $img = $this->getImgSrc($value);
            if ($img) {
              $col_name = Coordinate::stringFromColumnIndex($column++);
              $drawing = new Drawing();
              $drawing->setName($fieldName);
              $drawing->setPath($img);
              [$img_width, $img_height] = getimagesize($img);
              $width = $img_width * 0.1583;
              $height = $img_height * 0.75;
              $drawing->setHeight($height);
              $drawing->setCoordinates($col_name . ($row + 1));
              $drawing->setWorksheet($worksheet);
              $worksheet->getRowDimension($row + 1)->setRowHeight($height);
              $worksheet->getStyle('A' . ($row + 1) . ':' . $col_name . $totalCol)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
              $worksheet->getColumnDimension($col_name)->setAutoSize(FALSE);
              $worksheet->getColumnDimension($col_name)->setWidth($width);
            }
            else {
              $worksheet->setCellValueByColumnAndRow($column++, $row + 1, $this->formatValue($value));
            }
          }
          else {
            $matches = [];
            if (preg_match($a_tag_pattern, $value, $matches)) {
              $href = $matches[1];
              $col_name = Coordinate::stringFromColumnIndex($column++) . ($row + 1);
              $worksheet->setCellValue($col_name, $this->formatValue($value));
              $worksheet->getCell($col_name)->getHyperlink()->setUrl($href);
            }
            elseif (preg_match($url_pattern, $value)) {
              $col_name = Coordinate::stringFromColumnIndex($column++) . ($row + 1);
              $worksheet->setCellValue($col_name, $this->formatValue($value));
              $worksheet->getCell($col_name)->getHyperlink()->setUrl($value);
            }
            else {
              $worksheet->setCellValueByColumnAndRow($column++, $row + 1, $this->formatValue($value));
            }
          }
        }
      }

      // Fill color.
      if ($colors) {
        if (!empty($options["xls_settings"]['row_color'])) {
          $rows = explode(',', $options["xls_settings"]['row_color']);
          foreach ($colors as $coordinate => $color) {
            foreach ($rows as $row) {
              $worksheet->getStyle($coordinate . trim($row))
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB($color);
            }
          }
        }
        else {
          foreach ($colors as $coordinate => $color) {
            $worksheet->getStyle($coordinate)
              ->getFill()
              ->setFillType(Fill::FILL_SOLID)
              ->getStartColor()
              ->setARGB($color);
          }
        }
      }
      // Set style if footer.
      if (!empty($footer) && ((empty($offset) && empty($limit)) || (!empty($offset) && $total_rows < $limit))) {
        $worksheet->getStyle('A' . $row)->applyFromArray($bold);
        $worksheet->mergeCellsByColumnAndRow(1, $row + 1, $totalCol, $row + 1);
      }
      $writer = IOFactory::createWriter($spreadsheet, ucfirst($this->format));
      ob_start();
      $writer->save('php://output');
      $spreadsheet->disconnectWorksheets();
      return ob_get_clean();
    }
    catch (\Exception $e) {
      throw new InvalidDataTypeException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * Create a new PhpSpreadsheet.
   */
  public function createSpreadsheet() {
    $spreadsheet = new Spreadsheet();
    $spreadsheet->setActiveSheetIndex(0);
    return $spreadsheet;
  }

  /**
   * Filter value.
   */
  protected function formatValue($value) {
    $value = Html::decodeEntities($value);
    $value = strip_tags($value);
    return trim($value);
  }

  /**
   * Get image src.
   */
  protected function getImgSrc($img) {
    $img = trim(strip_tags($img, '<img>'));
    $doc = new \DOMDocument();
    $doc->loadHTML($img);
    $xpath = new \DOMXPath($doc);
    $src = $xpath->evaluate("string(//img/@src)");
    if (!empty($src)) {
      $img = $src;
    }
    // Remove url absolute.
    $url = Url::fromUserInput('/', ['absolute' => TRUE])->toString();
    $img = str_replace($url, '', $img);
    $urlParse = parse_url($img);
    $path_parts = pathinfo($urlParse['path']);
    $extensionAllow = ['png', 'jpg', 'jpge', 'bmp', 'gif'];
    // Check if it's image.
    $ext = $path_parts['extension'] ?? '';
    if (!in_array($ext, $extensionAllow)) {
      return FALSE;
    }
    // Convert img to path.
    return $this->root . $urlParse['path'];
  }

}
