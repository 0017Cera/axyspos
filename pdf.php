<?php
require_once('fpdf/fpdf.php');

class PDF extends FPDF {
    function Header() {
        // Logo
        if (file_exists('image/logo.png')) {
            $this->Image('image/logo.png', 15, 10, 30);
        }
        
        // Company Name and Details
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 5, 'Axys Premiums Unlimited, Inc.', 0, 1, 'R');
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, 'Unit 2B Roofdeck Veraida 1 Condominium,', 0, 1, 'R');
        $this->Cell(0, 4, '120 Amorsolo St., Legaspi Village, Makati City', 0, 1, 'R');
        $this->Cell(0, 4, 'Tel Nos.: (632) 840-0913 to 14', 0, 1, 'R');
        $this->Cell(0, 4, 'Telefax: (632) 812-4876', 0, 1, 'R');
        $this->Cell(0, 4, 'Email: info@axys-premiums.com', 0, 1, 'R');
        $this->Cell(0, 4, 'Website: www.axys-premiums.com', 0, 1, 'R');
        
        // Title
        $this->Ln(10);
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'PURCHASE ORDER', 0, 1, 'C');
        $this->Line(15, $this->GetY(), 195, $this->GetY());
    }
}
?> 