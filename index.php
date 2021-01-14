<?php

class Styles
{
	public static function factory(): self
	{
		return new self;
	}

	public function render()
	{
		echo <<<HTML
		<style>
			table {border-collapse: collapse;}
			table, th, td {border: 1px solid black;}
			.row {
				display: table;
				width: 300px; /*Optional*/
				table-layout: fixed; /*Optional*/
				border-spacing: 3px; /*Optional*/
			}
			
			.row > * {
				display: table-cell;
			}
			label {display: block;min-width: 300px;}
			label {color: white;}
			form {
				border: 1px solid dimgrey;
				background-color: dimgrey;
				border-radius: 5px;
			}
			.line{
				text-transform: uppercase;
			}
		</style>
HTML;
	}
}

class Regions
{
	private $path = '';
	private $key = '';
	private $projectName = '';

	private $value = '';

	public function __construct($path, $key, $projectName)
	{
		$this->path = $path;
		$this->key = $key;
		$this->projectName = $projectName;
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		$user = exec('whoami');
		return str_replace('UUU', $user, $this->path);
	}

	/**
	 * @return string
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * @return string
	 */
	public function getProjectName()
	{
		return $this->projectName;
	}

	public function getValue()
	{
		$this->value = '';

		$this->readValue();

		return $this->value;
	}


	public function readValue()
	{
		$cmd = 'cat "'.$this->getPath().'" |  grep "'.$this->getKey().'"';
		$line = exec($cmd);

		if (strpos($line, '=') !== false) {
			$exploded = explode('=', $line);
			$this->value = trim($exploded[1] ?? '');

			return $this->value;
		}

		if (strpos($line, ':') !== false) {
			$exploded = explode(':', $line);
			$this->value = preg_replace('/["\' ,]/', '', ($exploded[1] ?? ''));

			return $this->value;
		}

		// widget case with define
		$matches = null;
		$pattern = '/define\([\s]*[\'"]'.$this->getKey().'[\'"][\s]*,[\s]*[\'"]([\w]+)[\'"][\s]*\)/';
		if (preg_match($pattern, $line, $matches)) {
			$this->value = $matches[1] ?? '';

			return $this->value;
		}

		return $line;
	}

	public function updateValue($value = 'US')
	{
		if (!$this->getValue()) {
			$this->readValue();
		}

		if ($this->value) {
			$cmd = 'cat "'.$this->getPath().'" |  grep "'.$this->getKey().'"';

			$line = exec($cmd);

			if (strpos($line, '=') !== false) {
				$exploded = explode('=', $line);

				$countOfReplacement = 0;

				$exploded[1] = str_replace($this->value, $value, $exploded[1], $countOfReplacement);

				if ($countOfReplacement !== 1) {
					throw new Exception('Replacement count not 1 (#1): '.strval($countOfReplacement));
				}

				$newLine = implode('=', $exploded);

				$cmdUpdate = 'sed -i "" "s/'.$line.'/'.$newLine.'/" "'.$this->getPath().'"';

				$r = exec($cmdUpdate);

				return;
			}

			if (strpos($line, ':') !== false) {
				$exploded = explode(':', $line);

				$countOfReplacement = 0;

				$exploded[1] = str_replace($this->value, $value, $exploded[1], $countOfReplacement);

				if ($countOfReplacement !== 1) {
					throw new Exception('Replacement count not 1 (#2): '.strval($countOfReplacement));
				}

				$newLine = implode(':', $exploded);

				$cmdUpdate = 'sed -i \'\' \'s/'.$line.'/'.$newLine.'/\' \''.$this->getPath().'\'';

				exec($cmdUpdate);

				return $this->value;
			}

			// widget case with define
			$pattern = '/define\([\s]*[\'"]'.$this->getKey().'[\'"][\s]*,[\s]*[\'"]([\w]+)[\'"][\s]*\)/';
			if (preg_match($pattern, $line, $matches)) {
				$this->value = $matches[1];

				$newLine = preg_replace($pattern, 'define(\''.$this->getKey().'\', \''.$value.'\')', $line, -1);

				$cmdUpdate = 'sed -i \'\' "s/'.$line.'/'.$newLine.'/" \''.$this->getPath().'\'';

				exec($cmdUpdate);

				return $this->value;
			}
		}
	}

	public function export()
	{
		return [
			'project' => $this->getProjectName(),
			'key' => $this->getKey(),
			'path' => $this->getPath(),
			'value' => $this->getValue(),
		];
	}
}

class Table
{
	private $data = [];

	private static $isStylesRendered = false;

	/**
	 * Table constructor.
	 * @param  array  $data
	 */
	public function __construct(array $data = [])
	{
		$this->data = $data;
	}

	public static function factory(array $data = [])
	{
		return new self($data);
	}

	public function render()
	{
		if (!self::$isStylesRendered) {

//			echo '<pre>';
			self::$isStylesRendered = true;
		}

		foreach ($this->data as $index => $row) {
			if ($index === 0) {
				echo '<table><tr>';
				echo $this->renderCell('O/n');
				foreach (array_keys($row) as $title) {
					echo $this->renderCell($title, true);
				}
				echo '</tr>';
			}
			echo '<tr>';
			echo $this->renderCell($index + 1);
			foreach ($row as $item) {
				echo $this->renderCell($item);
			}
			echo '</tr>';
		}

		echo '</table>';
	}

	private function renderCell($value, $isHead = false)
	{
		if ($isHead) {
			return "<th>$value</th>";
		}
		return "<td>$value</td>";
	}

	private function renderRow($data = [])
	{
	}
}

class Form
{
	private $inputs = [];

	public static function factory(): self
	{
		return new self;
	}

	public function addInput($type = 'text', $name = null, $value = null, $label = null): self
	{
		$labelToRender = $label ? $label.':' : null;

		$value = $_REQUEST[$name] ?? $value;

		$this->inputs[] = <<<HTML
		<div class="row">
			<label>{$labelToRender}</label>
			<input type="{$type}" value="{$value}" placeholder="{$label}" name="{$name}" id="{$name}">
		</div>
HTML;

		return $this;

	}

	public function render()
	{
		echo '<form>';
		foreach ($this->inputs as $input) {
			echo $input;
		}
		echo '</form>';
	}
}

$data = [
	new Regions('/Users/UUU/Pay Later Group/micro-services/mobile/.env', 'REGION_ID', 'mobile'),
	new Regions('/Users/UUU/Pay Later Group/micro-services/mobile/.env', 'DEFAULT_JURISDICTION', 'mobile'),

	new Regions('/Users/UUU/Pay Later Group/micro-services/accounts/.env', 'REGION_ID', 'accounts'),
	new Regions('/Users/UUU/Pay Later Group/micro-services/accounts/.env', 'DEFAULT_JURISDICTION', 'accounts'),

	new Regions('/Users/UUU/Pay Later Group/micro-services/documents/.env', 'REGION_ID', 'documents'),
	new Regions('/Users/UUU/Pay Later Group/micro-services/documents/.env', 'DEFAULT_JURISDICTION', 'documents'),

	new Regions('/Users/UUU/Pay Later Group/micro-services/address/.env', 'REGION_ID', 'address'),
	new Regions('/Users/UUU/Pay Later Group/micro-services/address/.env', 'DEFAULT_JURISDICTION', 'address'),

	new Regions('/Users/UUU/Pay Later Group/micro-services/payments/.env', 'REGION_ID', 'payments'),
	new Regions('/Users/UUU/Pay Later Group/micro-services/payments/.env', 'DEFAULT_JURISDICTION', 'payments'),

	new Regions('/Users/UUU/Pay Later Group/micro-services/merchant/.env', 'REGION_ID', 'merchant'),
	new Regions('/Users/UUU/Pay Later Group/micro-services/merchant/.env', 'DEFAULT_JURISDICTION', 'merchant'),

	new Regions('/Users/UUU/Pay Later Group/micro-services/merchant-portal/.env', 'REGION_ID', 'merchant-portal'),
	new Regions('/Users/UUU/Pay Later Group/micro-services/merchant-portal/.env', 'DEFAULT_JURISDICTION',
		'merchant-portal'),

	new Regions('/Users/UUU/Pay Later Group/micro-services/customer-portal/.env', 'REGION_ID', 'customer-portal'),
	new Regions('/Users/UUU/Pay Later Group/micro-services/customer-portal/.env', 'DEFAULT_JURISDICTION',
		'customer-portal'),

	new Regions('/Users/UUU/Pay Later Group/micro-services/communications/.env', 'REGION_ID', 'communications'),
	new Regions('/Users/UUU/Pay Later Group/micro-services/communications/.env', 'DEFAULT_JURISDICTION',
		'communications'),

	new Regions('/Users/UUU/Pay Later Group/micro-service-manager/.env', 'REGION_ID', 'micro-service-manager'),
	new Regions('/Users/UUU/Pay Later Group/micro-service-manager/.env', 'DEFAULT_JURISDICTION',
		'micro-service-manager'),

	new Regions('/Users/UUU/Pay Later Group/micro-services/external-service/.env', 'REGION_ID', 'external-service'),
	new Regions('/Users/UUU/Pay Later Group/micro-services/external-service/.env', 'DEFAULT_JURISDICTION',
		'external-service'),

	new Regions('/Users/UUU/Pay Later Group/legacy/Admin/.config', 'region.id', 'admin'),

	new Regions('/Users/UUU/Pay Later Group/legacy/LMP/.config', 'region.id', 'lmp'),

	new Regions('/Users/UUU/git.paylatergroup.com/code/Micro-Service-Consumer-Level-Lending-API/.env', 'REGION_ID','CLL - old'),
	new Regions('/Users/UUU/git.paylatergroup.com/code/Micro-Service-Consumer-Level-Lending-API/.env',	'DEFAULT_JURISDICTION', 'CLL - old'),
	new Regions('/Users/UUU/git.paylatergroup.com/code/Micro-Service-Consumer-Level-Lending-API/.env',	'FNPL_REGION_COUNTRY_CODE', 'CLL - old'),

	new Regions('/Users/UUU/Pay Later Group/legacy/CLL/.env', 'REGION_ID','CLL - new'),
	new Regions('/Users/UUU/Pay Later Group/legacy/CLL/.env', 'DEFAULT_JURISDICTION', 'CLL - new'),
	new Regions('/Users/UUU/Pay Later Group/legacy/CLL/.env', 'FNPL_REGION_COUNTRY_CODE', 'CLL - new'),

	new Regions('/Users/UUU/Pay Later Group/Micro-Service-Central/.env', 'REGION_ID', 'Central - old'),
	new Regions('/Users/UUU/Pay Later Group/legacy/Central/.env', 'REGION_ID', 'Central - new'),

	new Regions('/Users/UUU/Pay Later Group/application-widget/configuration.php', 'REGION_ID', 'widget'),

];


Styles::factory()->render();

Form::factory()
	->addInput('text', 'region', null, 'Region')
	->addInput('submit', 'submit', 'submit')
	->render();

echo '<div class="line">before:</div>';

$return = [];

foreach ($data as $item) {
	$return[] = $item->export();
}

Table::factory($return)->render();

echo '<div class="line">AFTER:</div>';

$return = [];

foreach ($data as $item) {

	if ($newRegion = ($_REQUEST['region'] ?? null)) {
		$item->updateValue($newRegion);
	}

	$return[] = $item->export();
}

Table::factory($return)->render();