<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Support\CellSanitizer;
use App\Support\Format;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Pembuat berkas .xlsx rekap pendaftaran dan hasil lomba.
 */
class Excel_exporter
{
	private $CI;

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->model(array(
			'Competition_model', 'Entry_model', 'Village_model', 'Result_model', 'Score_model', 'Audit_model', 'User_model',
		));
	}

	/** Rekap pendaftaran: satu baris per peserta. */
	public function participants(?int $competitionId, ?int $villageId): Spreadsheet
	{
		$entries = $this->CI->Entry_model->admin_list($competitionId, $villageId);

		$rows = array();
		foreach ($entries as $e)
		{
			foreach ($e['members'] === array() ? array(NULL) : $e['members'] as $m)
			{
				$rows[] = array(
					$e['competition_name'],
					$e['village_name'],
					$m['name'] ?? '-',
					$m['age'] ?? '-',
					($m['gender'] ?? NULL) === 'L' ? 'Putra' : ((($m['gender'] ?? NULL) === 'P') ? 'Putri' : '-'),
					$m['education_level'] ?? '-',
					$m['grade'] ?? '-',
					$e['entry_type'] === 'team' ? 'Tim' : ($e['entry_type'] === 'individual' ? 'Individu' : ($e['type_label'] ?? '-')),
					$e['status'] === 'registered' ? 'Terdaftar' : 'Didiskualifikasi',
					$e['source'] === 'public' ? 'Kiriman publik' : 'Admin desa',
					$e['submission_url'] ?? '-',
					$m['parent_name'] ?? ($e['parent_name'] ?? '-'),
					$e['ig_username'] ? '@'.$e['ig_username'] : '-',
					$e['members_note'] ?? '-',
				);
			}
		}

		$ss = new Spreadsheet();
		$used = array();
		$this->add_sheet($ss, $used, 'Pendaftaran',
			array('Lomba', 'Desa', 'Nama Peserta / Tim', 'Usia', 'Gender', 'Jenjang', 'Kelas', 'Jenis', 'Status', 'Sumber', 'Link Karya', 'Nama Orang Tua', 'Instagram', 'Anggota Tim'), $rows);

		$counts = $this->CI->Entry_model->counts_by_competition_village();
		$sum = array();
		foreach ($this->CI->Village_model->all() as $v)
		{
			$lomba = 0;
			$entriesTotal = 0;
			foreach ($counts as $perVillage)
			{
				if (isset($perVillage[(int) $v['id']]))
				{
					$lomba++;
					$entriesTotal += $perVillage[(int) $v['id']];
				}
			}
			$sum[] = array($v['name'], $lomba, $entriesTotal);
		}
		$this->add_sheet($ss, $used, 'Ringkasan Desa', array('Desa', 'Lomba Terisi', 'Pendaftaran Aktif'), $sum);

		return $ss;
	}

	/** Rekap nilai seluruh lomba aktif. */
	public function results(): Spreadsheet
	{
		$ss = new Spreadsheet();
		$used = array();

		$summary = array();
		$sheets = array();
		$detailRows = array();

		foreach ($this->CI->Result_model->all_competitions() as $block)
		{
			$c = $block['competition'];
			$results = $block['results'];
			$sheets[] = array($c, $results);

			$active = array_filter($results, static fn ($r) => $r['status'] === 'registered');
			$summary[] = array(
				$c['name'],
				count($active),
				count($results) - count($active),
				count($this->CI->Competition_model->judge_ids($c['id'])),
			);
		}
		foreach ($this->CI->Score_model->submissions() as $s)
		{
			foreach ($s['details'] as $d)
			{
				$detailRows[] = array($s['competition_name'], $s['village_name'], $s['entry_label'], $s['judge_name'], $d['name'], $d['score'], $d['max_score']);
			}
		}

		$this->add_sheet($ss, $used, 'Ringkasan', array('Lomba', 'Peserta Aktif', 'Didiskualifikasi', 'Juri Ditugaskan'), $summary);

		foreach ($sheets as [$c, $results])
		{
			$isCerdasCermat = $this->CI->Competition_model->is_cerdas_cermat($c);
			$judgeHeaders = array();
			foreach ($results as $r)
			{
				$judgeHeaders += $r['judge_names'];
			}
			$rows = array();
			foreach ($results as $r)
			{
				$row = array(
					$r['rank'] ?? '-',
					$r['village_name'],
					$r['label'].($r['status'] === 'disqualified' ? ' (didiskualifikasi)' : ''),
				);
				if ($isCerdasCermat)
				{
					foreach ($r['round_scores'] as $roundScore)
					{
						$row[] = round($roundScore, 2);
					}
					$row[] = $r['penalty'] > 0 ? round($r['penalty'], 2) : 0;
					$row[] = $r['final_score'] !== NULL ? round($r['final_score'], 2) : '-';
					$row[] = $r['is_winner'] ? 'Ya' : '';
				}
				else
				{
					foreach (array_keys($judgeHeaders) as $jid)
					{
						$row[] = isset($r['judge_totals'][$jid]) ? round($r['judge_totals'][$jid], 2) : '-';
					}
					$row[] = $r['avg_total'] !== NULL ? round($r['avg_total'], 2) : '-';
					$row[] = $r['penalty'] > 0 ? round($r['penalty'], 2) : 0;
					$row[] = $r['final_score'] !== NULL ? round($r['final_score'], 2) : '-';
					$row[] = $r['is_winner'] ? 'Ya' : '';
				}
				$rows[] = $row;
			}
			$scoreHeaders = $isCerdasCermat
				? array('Babak 1', 'Babak 2', 'Babak 3', 'Babak 4', 'Pengurangan', 'Total Sementara', 'Menang?')
				: array_merge(array_values($judgeHeaders), array('Rata-rata Nilai Juri', 'Penalti', 'Nilai Akhir', 'Menang?'));
			$this->add_sheet($ss, $used, $c['name'],
				array_merge(array('Peringkat', 'Desa', 'Peserta'), $scoreHeaders),
				$rows);
		}

		$this->add_sheet($ss, $used, 'Rincian Juri', array('Lomba', 'Desa', 'Peserta', 'Juri', 'Kriteria', 'Nilai', 'Nilai Maks'), $detailRows);

		$audit = array();
		foreach ($this->CI->Audit_model->recent(NULL, 5000) as $a)
		{
			$audit[] = array(
				Format::dateTime($a['changed_at']), $a['action'] === 'edit' ? 'Koreksi' : 'Reset juri', $a['competition_name'],
				$a['village_name'], $a['entry_label'], $a['judge_name'], $a['criterion_name'] ?? '-',
				$a['old_score'] !== NULL ? (float) $a['old_score'] : '-', $a['new_score'] !== NULL ? (float) $a['new_score'] : '-',
				$a['reason'], $a['changed_by_name'],
			);
		}
		$this->add_sheet($ss, $used, 'Riwayat Koreksi',
			array('Waktu', 'Aksi', 'Lomba', 'Desa', 'Peserta', 'Juri', 'Kriteria', 'Nilai Lama', 'Nilai Baru', 'Alasan', 'Oleh'), $audit);

		return $ss;
	}

	/** Kirim workbook sebagai unduhan lewat CI Output. */
	public function download(Spreadsheet $ss, string $prefix): void
	{
		// ZipArchive membutuhkan berkas nyata; hapus segera setelah dibaca.
		$tmp = tempnam(sys_get_temp_dir(), 'fgu');
		try
		{
			(new Xlsx($ss))->save($tmp);
			$bytes = file_get_contents($tmp);
		}
		finally
		{
			@unlink($tmp);
		}

		$filename = sprintf('FGU3-%s-%s.xlsx', $prefix, date('Y-m-d'));
		$this->CI->output
			->set_status_header(200)
			->set_content_type('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
			->set_header('Content-Disposition: attachment; filename="'.$filename.'"')
			->set_header('Cache-Control: no-store')
			->set_header('Content-Length: '.strlen($bytes))
			->set_output($bytes);
	}

	/** @param list<string> $used nama sheet yang sudah dipakai (diubah) */
	private function add_sheet(Spreadsheet $ss, array &$used, string $title, array $headers, array $rows): Worksheet
	{
		$ws = $used === array() ? $ss->getActiveSheet() : $ss->createSheet();
		$name = CellSanitizer::sheetName($title, $used);
		$used[] = $name;
		$ws->setTitle($name);

		$widths = array_map(static fn ($h) => mb_strlen((string) $h), $headers);
		$all = array_merge(array($headers), $rows);
		foreach ($all as $r => $row)
		{
			foreach (array_values($row) as $c => $value)
			{
				$cell = $ws->getCell([$c + 1, $r + 1]);
				$value = CellSanitizer::cell($value);
				if (is_string($value))
				{
					$cell->setValueExplicit($value, DataType::TYPE_STRING);
				}
				else
				{
					$cell->setValue($value);
				}
				$widths[$c] = max($widths[$c] ?? 0, min(50, mb_strlen((string) $value)));
			}
		}

		$lastCol = max(1, count($headers));
		$headerRange = $ws->getStyle([1, 1, $lastCol, 1]);
		$headerRange->getFont()->setBold(TRUE)->getColor()->setRGB('FFFFFF');
		$headerRange->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0878D1');
		$ws->freezePane('A2');
		$ws->setAutoFilter($ws->calculateWorksheetDimension());
		foreach ($widths as $i => $w)
		{
			$ws->getColumnDimensionByColumn($i + 1)->setWidth(min(50, $w + 3));
		}

		return $ws;
	}
}
