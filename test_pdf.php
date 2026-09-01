<?php

require_once(__DIR__ . '/tcpdf/tcpdf.php');

$pdf = new TCPDF();
$pdf->Addpage();
$pdf->setFont('helvetica', '', 12);
$pdf->Write(0, 'PDF inafanya kazi vizuri');

$pdf->Output();