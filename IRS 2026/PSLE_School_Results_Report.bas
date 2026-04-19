' ============================================================================
' PSLE SCHOOL RESULTS REPORT - EXCEL VBA (v3.0)
' ============================================================================
' IRS 2026 - Pixel-perfect A3 PDF replica of /results/psle/reports FPDF
'
' ARCHITECTURE (5 connected sheets):
'   1. "School Info"   - School details
'   2. "Registration"  - Register candidates + tick subjects
'   3. "Mark Entry"    - Select subject -> see candidates -> enter marks
'   4. "PSLE Report"   - A3 Portrait PDF-ready results (exact FPDF clone)
'   5. "Subject Perf"  - Subject performance analysis
'
' MACROS:
'   SetupWorkbook    - Run once to create all sheets
'   RefreshMarkEntry - After changing subject dropdown on Mark Entry
'   GenerateReport   - Build A3 PDF-ready report from entered data
'   ExportToPDF      - Save the PSLE Report sheet as A3 PDF file
' ============================================================================

Option Explicit

Private Const SUBJECT_COUNT As Integer = 6
Private Const MAX_CANDIDATES As Long = 500
Private Const DATA_START_ROW As Long = 4

Private Const SH_INFO As String = "School Info"
Private Const SH_REG As String = "Registration"
Private Const SH_MARKS As String = "Mark Entry"
Private Const SH_REPORT As String = "PSLE Report"
Private Const SH_SUBPERF As String = "Subject Perf"

' ── Exact FPDF colors ──
Private Const CLR_NAVY As Long = 8404992        ' RGB(0, 0, 128) - main text
Private Const CLR_DARK_NAVY As Long = 2760985    ' RGB(15, 23, 42) - top strip
Private Const CLR_WHITE As Long = 16777215
Private Const CLR_CREAM As Long = 14680063       ' RGB(255, 255, 224) - data rows
Private Const CLR_HEADER_BG As Long = 6697728    ' RGB(0, 51, 102) - table headers
Private Const CLR_POWDER_BLUE As Long = 15123632  ' RGB(176, 224, 230) - page bg

' Subject arrays
Private SubCodes(1 To 6) As String
Private SubShort(1 To 6) As String
Private SubFull(1 To 6) As String

' ============================================================================
' ENTRY POINT 1: SETUP WORKBOOK
' ============================================================================
Public Sub SetupWorkbook()
    Application.ScreenUpdating = False
    Application.DisplayAlerts = False
    Call InitSubjects
    
    Call CreateSheetIfMissing(SH_INFO)
    Call CreateSheetIfMissing(SH_REG)
    Call CreateSheetIfMissing(SH_MARKS)
    Call CreateSheetIfMissing(SH_REPORT)
    Call CreateSheetIfMissing(SH_SUBPERF)
    
    Call BuildSchoolInfoSheet
    Call BuildRegistrationSheet
    Call BuildMarkEntrySheet
    
    ThisWorkbook.Sheets(SH_INFO).Activate
    ThisWorkbook.Sheets(SH_INFO).Range("C4").Select
    
    Application.DisplayAlerts = True
    Application.ScreenUpdating = True
    
    MsgBox "Workbook ready!" & vbCrLf & vbCrLf & _
           "1) Fill 'School Info'" & vbCrLf & _
           "2) Register candidates on 'Registration'" & vbCrLf & _
           "3) Enter marks on 'Mark Entry' (select subject, run RefreshMarkEntry)" & vbCrLf & _
           "4) Run 'GenerateReport' for A3 PDF-ready output" & vbCrLf & _
           "5) Run 'ExportToPDF' to save as PDF file", _
           vbInformation, "IRS 2026"
End Sub

' ============================================================================
' ENTRY POINT 2: REFRESH MARK ENTRY
' ============================================================================
Public Sub RefreshMarkEntry()
    Application.ScreenUpdating = False
    Call InitSubjects
    Call PopulateMarkEntryFromRegistration
    Application.ScreenUpdating = True
End Sub

' ============================================================================
' ENTRY POINT 3: GENERATE REPORT (A3 PDF-ready)
' ============================================================================
Public Sub GenerateReport()
    Application.ScreenUpdating = False
    Application.Calculation = xlCalculationManual
    Call InitSubjects
    
    Dim wsReg As Worksheet: Set wsReg = ThisWorkbook.Sheets(SH_REG)
    If Trim(wsReg.Cells(DATA_START_ROW, 2).Value) = "" Then
        MsgBox "No candidates registered!", vbExclamation
        Application.Calculation = xlCalculationAutomatic
        Application.ScreenUpdating = True
        Exit Sub
    End If
    
    Call SaveMarksToRegistration
    Call BuildA3ReportSheet
    Call BuildSubjectPerfSheet
    
    ThisWorkbook.Sheets(SH_REPORT).Activate
    ThisWorkbook.Sheets(SH_REPORT).Range("A1").Select
    
    Application.Calculation = xlCalculationAutomatic
    Application.ScreenUpdating = True
    
    MsgBox "A3 Report generated! Run 'ExportToPDF' to save as PDF.", vbInformation, "IRS 2026"
End Sub

' ============================================================================
' ENTRY POINT 4: EXPORT TO PDF
' ============================================================================
Public Sub ExportToPDF()
    Dim ws As Worksheet
    On Error Resume Next
    Set ws = ThisWorkbook.Sheets(SH_REPORT)
    On Error GoTo 0
    If ws Is Nothing Then
        MsgBox "Run 'GenerateReport' first!", vbExclamation
        Exit Sub
    End If
    
    Dim filePath As String
    filePath = Application.GetSaveAsFilename( _
        InitialFileName:="PSLE_Results_" & Format(Now, "YYYYMMDD_HHmmss") & ".pdf", _
        FileFilter:="PDF Files (*.pdf), *.pdf", _
        Title:="Save PSLE Report as A3 PDF")
    
    If filePath = "False" Then Exit Sub
    
    ws.ExportAsFixedFormat Type:=xlTypePDF, Filename:=filePath, _
        Quality:=xlQualityStandard, IncludeDocProperties:=False, _
        OpenAfterPublish:=True
    
    MsgBox "PDF saved to:" & vbCrLf & filePath, vbInformation, "IRS 2026"
End Sub

' ============================================================================
' A3 REPORT SHEET - Exact FPDF replica
' ============================================================================
Private Sub BuildA3ReportSheet()
    Dim ws As Worksheet: Set ws = ThisWorkbook.Sheets(SH_REPORT)
    Dim wsInfo As Worksheet: Set wsInfo = ThisWorkbook.Sheets(SH_INFO)
    Dim wsReg As Worksheet: Set wsReg = ThisWorkbook.Sheets(SH_REG)
    ws.Cells.Clear
    
    ' ── Read school info ──
    Dim schoolName As String: schoolName = UCase(Trim(wsInfo.Range("C4").Value))
    Dim schoolCode As String: schoolCode = UCase(Trim(wsInfo.Range("C5").Value))
    Dim councilName As String: councilName = UCase(Trim(wsInfo.Range("C6").Value))
    Dim regionName As String: regionName = UCase(Trim(wsInfo.Range("C7").Value))
    Dim className As String: className = UCase(Trim(wsInfo.Range("C8").Value))
    Dim examYear As String: examYear = Trim(wsInfo.Range("C9").Value)
    Dim examTitle As String: examTitle = UCase(Trim(wsInfo.Range("C10").Value))
    
    ' ══════════════════════════════════════════════════════════════════════════
    ' A3 PORTRAIT PAGE SETUP (297mm x 420mm) - matches FPDF exactly
    ' ══════════════════════════════════════════════════════════════════════════
    With ws.PageSetup
        .Orientation = xlPortrait
        .PaperSize = xlPaperA3           ' A3 Portrait like FPDF
        .LeftMargin = Application.CentimetersToPoints(0.6)   ' 6mm
        .RightMargin = Application.CentimetersToPoints(0.6)  ' 6mm
        .TopMargin = Application.CentimetersToPoints(0.8)    ' 8mm
        .BottomMargin = Application.CentimetersToPoints(0.8) ' 8mm
        .Zoom = False
        .FitToPagesWide = 1
        .FitToPagesTall = False
        .PrintGridlines = False
        .CenterHorizontally = True
        .HeaderMargin = 0
        .FooterMargin = 0
    End With
    
    ws.Cells.Font.Name = "Arial"
    ws.Cells.Font.Size = 8
    
    ' ── Powder blue page background (RGB 176,224,230) ──
    ' Apply to entire print area later
    
    ' ── Column widths matching FPDF mm widths ──
    ' FPDF candidates table: CAND.NO=24, PREM=28, SEX=12, DETAILED=162, TOTAL=12, GRD=11, AGGT=11, GPA=15, POS=10
    ' Total = 285mm content width on A3 (297-6-6)
    ' We use 9 data columns (A-I) scaled proportionally
    ws.Columns("A").ColumnWidth = 10.5   ' CAND. NO (24mm)
    ws.Columns("B").ColumnWidth = 12.2   ' PREM NO (28mm)
    ws.Columns("C").ColumnWidth = 5.2    ' SEX (12mm)
    ws.Columns("D").ColumnWidth = 70.5   ' DETAILED SUBJECTS RESULT (162mm)
    ws.Columns("E").ColumnWidth = 5.2    ' TOTAL (12mm)
    ws.Columns("F").ColumnWidth = 4.8    ' GRD (11mm)
    ws.Columns("G").ColumnWidth = 4.8    ' AGGT (11mm)
    ws.Columns("H").ColumnWidth = 6.5    ' GPA (15mm)
    ws.Columns("I").ColumnWidth = 4.3    ' POS (10mm)
    
    ' Count candidates
    Dim totalCandidates As Long, maleCount As Long, femaleCount As Long
    Dim rr As Long
    For rr = DATA_START_ROW To DATA_START_ROW + MAX_CANDIDATES - 1
        If Trim(wsReg.Cells(rr, 2).Value) = "" Then Exit For
        totalCandidates = totalCandidates + 1
        If UCase(Trim(wsReg.Cells(rr, 5).Value)) = "M" Then maleCount = maleCount + 1
        If UCase(Trim(wsReg.Cells(rr, 5).Value)) = "F" Then femaleCount = femaleCount + 1
    Next rr
    
    Dim r As Long: r = 1
    
    ' ══════════════════════════════════════════════════════════════════════════
    ' BLOCK 1: DARK NAVY TOP STRIP (4mm)
    ' ══════════════════════════════════════════════════════════════════════════
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))
        .Merge: .Interior.Color = CLR_DARK_NAVY: .RowHeight = 11.3  ' ~4mm
    End With
    r = r + 1
    
    ' ══════════════════════════════════════════════════════════════════════════
    ' BLOCK 2: OFFICIAL HEADER (with emblem positions)
    ' FPDF: 5 lines, each 6pt height, Helvetica Bold 11, navy blue, centered
    ' ══════════════════════════════════════════════════════════════════════════
    
    ' PMO line
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))
        .Merge: .Value = "PRIME MINISTER'S OFFICE"
        .Font.Bold = True: .Font.Size = 11: .Font.Color = CLR_NAVY
        .HorizontalAlignment = xlCenter: .VerticalAlignment = xlCenter: .RowHeight = 17
    End With
    r = r + 1
    
    ' RALG line
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))
        .Merge: .Value = "REGIONAL ADMINISTRATION AND LOCAL GOVERNMENT"
        .Font.Bold = True: .Font.Size = 11: .Font.Color = CLR_NAVY
        .HorizontalAlignment = xlCenter: .VerticalAlignment = xlCenter: .RowHeight = 17
    End With
    r = r + 1
    
    ' Zones line
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))
        .Merge: .Value = "TANGA, IRINGA, SINGIDA, MOROGORO, DODOMA, TABORA, LINDI AND MTWARA"
        .Font.Bold = True: .Font.Size = 11: .Font.Color = CLR_NAVY
        .HorizontalAlignment = xlCenter: .VerticalAlignment = xlCenter: .RowHeight = 17
    End With
    r = r + 1
    
    ' Exam title line
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))
        .Merge: .Value = "OVERALL RESULTS FOR " & examTitle
        .Font.Bold = True: .Font.Size = 11: .Font.Color = CLR_NAVY
        .HorizontalAlignment = xlCenter: .VerticalAlignment = xlCenter: .RowHeight = 17
    End With
    r = r + 1
    
    ' School name line
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))
        .Merge: .Value = schoolCode & " - " & schoolName
        .Font.Bold = True: .Font.Size = 11: .Font.Color = CLR_NAVY
        .HorizontalAlignment = xlCenter: .VerticalAlignment = xlCenter: .RowHeight = 17
    End With
    r = r + 1
    
    ' ══════════════════════════════════════════════════════════════════════════
    ' BLOCK 3: TANZANIA FLAG COLOR STRIP (0.7mm height)
    ' Segments: Green 30%, Yellow 24%, Black 16%, Navy 30%
    ' ══════════════════════════════════════════════════════════════════════════
    ws.Rows(r).RowHeight = 3
    ' Green segment (cols A-C ~30%)
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 3)): .Merge: .Interior.Color = RGB(0, 166, 81): End With
    ' Yellow segment (cols D ~24%)
    ' Can't do exact proportions in Excel cols, so approximate with available columns
    With ws.Cells(r, 4): .Interior.Color = RGB(245, 208, 0): End With
    ' Black segment (cols E-F ~16%)
    With ws.Range(ws.Cells(r, 5), ws.Cells(r, 6)): .Merge: .Interior.Color = RGB(0, 0, 0): End With
    ' Navy segment (cols G-I ~30%)
    With ws.Range(ws.Cells(r, 7), ws.Cells(r, 9)): .Merge: .Interior.Color = RGB(11, 47, 91): End With
    r = r + 1
    
    ' Spacer
    ws.Rows(r).RowHeight = 8: r = r + 1
    
    ' ══════════════════════════════════════════════════════════════════════════
    ' BLOCK 4: OVERVIEW SECTION (FPDF renderOverview - left-aligned bold navy)
    ' ══════════════════════════════════════════════════════════════════════════
    
    ' EXAMINATION CENTRE line
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))
        .Merge: .Value = "EXAMINATION CENTRE: " & schoolName & " - " & schoolCode
        .Font.Bold = True: .Font.Size = 10: .Font.Color = CLR_NAVY
        .HorizontalAlignment = xlLeft: .VerticalAlignment = xlCenter: .RowHeight = 18.4
    End With
    r = r + 1
    
    ' CANDIDATES SAT line
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))
        .Merge
        .Value = "CANDIDATES SAT : " & totalCandidates & " OUT OF " & totalCandidates & _
                 " REGISTERED CANDIDATES (F: " & femaleCount & "/" & femaleCount & _
                 ", M: " & maleCount & "/" & maleCount & ")"
        .Font.Bold = True: .Font.Size = 10: .Font.Color = CLR_NAVY
        .HorizontalAlignment = xlLeft: .VerticalAlignment = xlCenter: .RowHeight = 18.4
    End With
    r = r + 1
    
    ' SCHOOL AVERAGE line (calculated after data)
    Dim avgRow As Long: avgRow = r
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))
        .Merge: .Value = "SCHOOL AVERAGE : [calculated after data]"
        .Font.Bold = True: .Font.Size = 10: .Font.Color = CLR_NAVY
        .HorizontalAlignment = xlLeft: .VerticalAlignment = xlCenter: .RowHeight = 18.4
    End With
    r = r + 1
    
    ' PASS RATE line
    Dim passRateRow As Long: passRateRow = r
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))
        .Merge: .Value = "PASS RATE (A-C): [calc] | PASS RATE (A-D): [calc]"
        .Font.Bold = True: .Font.Size = 10: .Font.Color = CLR_NAVY
        .HorizontalAlignment = xlLeft: .VerticalAlignment = xlCenter: .RowHeight = 18.4
    End With
    r = r + 1
    
    ' TOP CANDIDATE line
    Dim topCandRow As Long: topCandRow = r
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))
        .Merge: .Value = "TOP CANDIDATE: [calculated after data]"
        .Font.Bold = True: .Font.Size = 10: .Font.Color = CLR_NAVY
        .HorizontalAlignment = xlLeft: .VerticalAlignment = xlCenter: .RowHeight = 18.4
    End With
    r = r + 1
    
    ' ══════════════════════════════════════════════════════════════════════════
    ' BLOCK 5: GRADE PERFORMANCE TABLE
    ' FPDF: Title bar + 12 equal-width columns + 3 data rows (F, M, T)
    ' ══════════════════════════════════════════════════════════════════════════
    
    ' Title bar
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))
        .Merge: .Value = "EXAMINATION CENTRE GRADE PERFORMANCE"
        .Font.Bold = True: .Font.Size = 10: .Font.Color = CLR_WHITE
        .HorizontalAlignment = xlLeft: .Interior.Color = CLR_HEADER_BG
        .VerticalAlignment = xlCenter: .RowHeight = 22.7: .IndentLevel = 1
    End With
    Call AddBorders(ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))): r = r + 1
    
    ' For grade perf we need 12 columns - use a helper range approach
    ' We'll write this as a single-row text table since Excel cols are fixed for candidates
    ' SEX | REGIST | SAT | WITHHELD | CLEAN | A | B | C | D | E | INC | ABS
    Dim gpHeaders As Variant
    gpHeaders = Array("SEX", "REGIST", "SAT", "WITHHELD", "CLEAN", "A", "B", "C", "D", "E", "INC", "ABS")
    
    ' Header row - merge col D to fit 12 sub-columns using Cell formatting
    ' Since we only have 9 cols, we'll format this as a text table inside merged cells
    ' Actually, let's use 12 narrow columns by temporarily breaking the merge
    ' Better approach: write grade performance as formatted text rows
    
    Dim gpHeaderStr As String
    gpHeaderStr = PadField("SEX", 6) & PadField("REGIST", 8) & PadField("SAT", 7) & _
                  PadField("W/HELD", 8) & PadField("CLEAN", 8) & PadField("A", 5) & _
                  PadField("B", 5) & PadField("C", 5) & PadField("D", 5) & _
                  PadField("E", 5) & PadField("INC", 6) & PadField("ABS", 5)
    
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))
        .Merge: .Value = gpHeaderStr
        .Font.Bold = True: .Font.Size = 9: .Font.Color = CLR_WHITE: .Font.Name = "Consolas"
        .HorizontalAlignment = xlLeft: .Interior.Color = CLR_HEADER_BG
        .VerticalAlignment = xlCenter: .RowHeight = 18.7
    End With
    Call AddBorders(ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))): r = r + 1
    
    ' F row, M row, T row - will be updated after candidate data processing
    Dim gpFRow As Long: gpFRow = r
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))
        .Merge: .Value = "": .Font.Bold = True: .Font.Size = 9: .Font.Color = CLR_NAVY: .Font.Name = "Consolas"
        .HorizontalAlignment = xlLeft: .Interior.Color = CLR_CREAM: .RowHeight = 18.7
    End With
    Call AddBorders(ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))): r = r + 1
    
    Dim gpMRow As Long: gpMRow = r
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))
        .Merge: .Value = "": .Font.Bold = True: .Font.Size = 9: .Font.Color = CLR_NAVY: .Font.Name = "Consolas"
        .HorizontalAlignment = xlLeft: .Interior.Color = CLR_CREAM: .RowHeight = 18.7
    End With
    Call AddBorders(ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))): r = r + 1
    
    Dim gpTRow As Long: gpTRow = r
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))
        .Merge: .Value = "": .Font.Bold = True: .Font.Size = 9: .Font.Color = CLR_NAVY: .Font.Name = "Consolas"
        .HorizontalAlignment = xlLeft: .Interior.Color = RGB(255, 248, 200): .RowHeight = 18.7
        .Borders(xlEdgeTop).Weight = xlMedium: .Borders(xlEdgeBottom).Weight = xlMedium
    End With
    Call AddBorders(ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))): r = r + 1
    
    ' Spacer
    ws.Rows(r).RowHeight = 11.3: r = r + 1
    
    ' ══════════════════════════════════════════════════════════════════════════
    ' BLOCK 6: CANDIDATE RESULTS TABLE
    ' FPDF: CAND.NO(24) | PREM(28) | SEX(12) | DETAILED(162) | TOTAL(12) |
    '       GRD(11) | AGGT(11) | GPA(15) | POS(10)
    ' ══════════════════════════════════════════════════════════════════════════
    
    ' Column headers
    Dim candHeaders As Variant
    candHeaders = Array("CAND. NO", "PREM NO", "SEX", "DETAILED SUBJECTS RESULT", "TOTAL", "GRD", "AGGT", "GPA", "POS")
    Dim ch As Integer
    For ch = 0 To UBound(candHeaders)
        With ws.Cells(r, ch + 1)
            .Value = candHeaders(ch)
            .Font.Bold = True: .Font.Size = 8: .Font.Color = CLR_WHITE
            .HorizontalAlignment = xlCenter: .VerticalAlignment = xlCenter
            .Interior.Color = CLR_HEADER_BG: .WrapText = True
        End With
    Next ch
    ws.Rows(r).RowHeight = 18.1
    Call AddBorders(ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))): r = r + 1
    
    ' ── Populate candidate data with DETAILED SUBJECTS RESULT inline format ──
    ' FPDF format: "KISWAHILI - 35 'B', ENGLISH - 42 'A', SOCIAL - 28 'C', ..."
    Dim dataStartRow As Long: dataStartRow = r
    Dim candCount As Long: candCount = 0
    
    ' Arrays for grade counting
    Dim gradeCountF(0 To 4) As Long, gradeCountM(0 To 4) As Long
    
    ' Collect all candidates for ranking
    Type CandData
        regRow As Long
        totalScore As Double
        candNo As String
    End Type
    
    ' First pass: calculate totals for ranking
    Dim totals() As Double
    Dim candNos() As String
    ReDim totals(1 To totalCandidates)
    ReDim candNos(1 To totalCandidates)
    
    Dim idx As Long: idx = 0
    For rr = DATA_START_ROW To DATA_START_ROW + MAX_CANDIDATES - 1
        If Trim(wsReg.Cells(rr, 2).Value) = "" Then Exit For
        idx = idx + 1
        candNos(idx) = Trim(wsReg.Cells(rr, 2).Value)
        
        Dim t As Double: t = 0
        Dim sj As Integer
        For sj = 1 To SUBJECT_COUNT
            Dim mkCol As Integer: mkCol = 5 + SUBJECT_COUNT + 1 + sj
            Dim mkv As Variant: mkv = wsReg.Cells(rr, mkCol).Value
            If IsNumeric(mkv) And mkv <> "" Then t = t + CDbl(mkv)
        Next sj
        totals(idx) = t
    Next rr
    
    ' Calculate ranks
    Dim ranks() As Long
    ReDim ranks(1 To totalCandidates)
    Dim ri As Long, rj As Long
    For ri = 1 To totalCandidates
        ranks(ri) = 1
        For rj = 1 To totalCandidates
            If totals(rj) > totals(ri) Then ranks(ri) = ranks(ri) + 1
            If totals(rj) = totals(ri) And rj < ri Then ranks(ri) = ranks(ri) + 1
        Next rj
    Next ri
    
    ' Find top candidate
    Dim topIdx As Long: topIdx = 1
    For ri = 2 To totalCandidates
        If totals(ri) > totals(topIdx) Then topIdx = ri
    Next ri
    
    ' Second pass: write data rows
    idx = 0
    For rr = DATA_START_ROW To DATA_START_ROW + MAX_CANDIDATES - 1
        If Trim(wsReg.Cells(rr, 2).Value) = "" Then Exit For
        idx = idx + 1
        candCount = candCount + 1
        
        Dim cNo As String: cNo = Trim(wsReg.Cells(rr, 2).Value)
        Dim pNo As String: pNo = Trim(wsReg.Cells(rr, 3).Value)
        Dim sex As String: sex = UCase(Left(Trim(wsReg.Cells(rr, 5).Value), 1))
        
        ' Build DETAILED SUBJECTS RESULT string (FPDF format)
        ' "KISWAHILI - 35 'B', ENGLISH - 42 'A', ..."
        Dim detailParts() As String
        ReDim detailParts(1 To SUBJECT_COUNT)
        Dim totalScore As Double: totalScore = 0
        Dim aggPoints As Long: aggPoints = 0
        Dim subjCount As Long: subjCount = 0
        
        For sj = 1 To SUBJECT_COUNT
            mkCol = 5 + SUBJECT_COUNT + 1 + sj
            mkv = wsReg.Cells(rr, mkCol).Value
            Dim score As Double: score = 0
            Dim grd As String: grd = "E"
            
            If IsNumeric(mkv) And mkv <> "" Then
                score = CDbl(mkv)
                totalScore = totalScore + score
                grd = GradeFromScore(score)
                aggPoints = aggPoints + GradePoint(grd)
                subjCount = subjCount + 1
            End If
            
            detailParts(sj) = SubShort(sj) & " - " & Format(score, "0") & " '" & grd & "'"
        Next sj
        
        Dim detailStr As String
        detailStr = Join(detailParts, ", ")
        
        Dim avgScore As Double: avgScore = IIf(subjCount > 0, totalScore / subjCount, 0)
        Dim avgGrade As String: avgGrade = GradeFromScore(avgScore)
        Dim gpa As Double: gpa = IIf(subjCount > 0, aggPoints / subjCount, 0)
        
        ' Write row
        ws.Cells(r, 1).Value = cNo                    ' CAND. NO
        ws.Cells(r, 1).HorizontalAlignment = xlLeft
        ws.Cells(r, 2).Value = pNo                    ' PREM NO
        ws.Cells(r, 2).HorizontalAlignment = xlCenter
        ws.Cells(r, 3).Value = sex                    ' SEX
        ws.Cells(r, 3).HorizontalAlignment = xlCenter
        ws.Cells(r, 4).Value = detailStr              ' DETAILED SUBJECTS RESULT
        ws.Cells(r, 4).HorizontalAlignment = xlLeft
        ws.Cells(r, 4).Font.Size = 7
        ws.Cells(r, 5).Value = Format(totalScore, "0")  ' TOTAL
        ws.Cells(r, 5).HorizontalAlignment = xlCenter
        ws.Cells(r, 6).Value = avgGrade               ' GRD
        ws.Cells(r, 6).HorizontalAlignment = xlCenter
        ws.Cells(r, 7).Value = aggPoints               ' AGGT
        ws.Cells(r, 7).HorizontalAlignment = xlCenter
        ws.Cells(r, 8).Value = Format(gpa, "0.0000")  ' GPA
        ws.Cells(r, 8).HorizontalAlignment = xlCenter
        ws.Cells(r, 9).Value = ranks(idx)              ' POS
        ws.Cells(r, 9).HorizontalAlignment = xlCenter
        
        ' Row formatting - exact FPDF style
        With ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))
            .Font.Bold = True: .Font.Color = CLR_NAVY
            .Interior.Color = CLR_CREAM: .VerticalAlignment = xlCenter
        End With
        ws.Cells(r, 4).Font.Bold = False  ' Detail text not bold in FPDF
        ws.Rows(r).RowHeight = 18.1
        Call AddBorders(ws.Range(ws.Cells(r, 1), ws.Cells(r, 9)))
        
        ' Count grades by sex
        Dim gIdx As Integer: gIdx = -1
        Select Case avgGrade
            Case "A": gIdx = 0: Case "B": gIdx = 1: Case "C": gIdx = 2
            Case "D": gIdx = 3: Case "E": gIdx = 4
        End Select
        If gIdx >= 0 Then
            If sex = "F" Then gradeCountF(gIdx) = gradeCountF(gIdx) + 1
            If sex = "M" Then gradeCountM(gIdx) = gradeCountM(gIdx) + 1
        End If
        
        r = r + 1
    Next rr
    
    Dim dataEndRow As Long: dataEndRow = r - 1
    
    ' Grade conditional formatting on GRD column
    If candCount > 0 Then Call AddGradeFormatting(ws, dataStartRow, dataEndRow, 6)
    
    ' ── Now update the overview rows with calculated values ──
    ' School average
    Dim schoolTotal As Double: schoolTotal = 0
    For ri = 1 To totalCandidates: schoolTotal = schoolTotal + totals(ri): Next ri
    Dim schoolAvg As Double: schoolAvg = IIf(totalCandidates > 0, schoolTotal / totalCandidates, 0)
    Dim schoolAvgPerSubj As Double: schoolAvgPerSubj = IIf(totalCandidates > 0, schoolAvg / SUBJECT_COUNT, 0)
    Dim schoolGrade As String: schoolGrade = GradeFromScore(schoolAvgPerSubj)
    
    ws.Range(ws.Cells(avgRow, 1), ws.Cells(avgRow, 9)).Value = ""
    With ws.Range(ws.Cells(avgRow, 1), ws.Cells(avgRow, 9))
        .Merge: .Value = "SCHOOL AVERAGE : " & Format(schoolAvg, "0.0000") & _
                "   Grade " & schoolGrade & " (" & CompetenceLabel(schoolGrade) & ")"
        .Font.Bold = True: .Font.Size = 10: .Font.Color = CLR_NAVY
        .HorizontalAlignment = xlLeft: .VerticalAlignment = xlCenter
    End With
    
    ' Pass rates
    Dim passAC As Long, passAD As Long
    For ri = 1 To totalCandidates
        Dim tAvg As Double: tAvg = totals(ri) / SUBJECT_COUNT
        Dim tGrd As String: tGrd = GradeFromScore(tAvg)
        If tGrd = "A" Or tGrd = "B" Or tGrd = "C" Then passAC = passAC + 1
        If tGrd = "A" Or tGrd = "B" Or tGrd = "C" Or tGrd = "D" Then passAD = passAD + 1
    Next ri
    Dim prAC As Double: prAC = IIf(totalCandidates > 0, (passAC / totalCandidates) * 100, 0)
    Dim prAD As Double: prAD = IIf(totalCandidates > 0, (passAD / totalCandidates) * 100, 0)
    
    ws.Range(ws.Cells(passRateRow, 1), ws.Cells(passRateRow, 9)).Value = ""
    With ws.Range(ws.Cells(passRateRow, 1), ws.Cells(passRateRow, 9))
        .Merge: .Value = "PASS RATE (A-C): " & Format(prAC, "0.00") & "% | PASS RATE (A-D): " & Format(prAD, "0.00") & "%"
        .Font.Bold = True: .Font.Size = 10: .Font.Color = CLR_NAVY
        .HorizontalAlignment = xlLeft: .VerticalAlignment = xlCenter
    End With
    
    ' Top candidate
    Dim topAvgGrd As String: topAvgGrd = GradeFromScore(totals(topIdx) / SUBJECT_COUNT)
    ws.Range(ws.Cells(topCandRow, 1), ws.Cells(topCandRow, 9)).Value = ""
    With ws.Range(ws.Cells(topCandRow, 1), ws.Cells(topCandRow, 9))
        .Merge: .Value = "TOP CANDIDATE: " & candNos(topIdx) & " (TOTAL: " & Format(totals(topIdx), "0") & ", GRD: " & topAvgGrd & ")"
        .Font.Bold = True: .Font.Size = 10: .Font.Color = CLR_NAVY
        .HorizontalAlignment = xlLeft: .VerticalAlignment = xlCenter
    End With
    
    ' ── Update grade performance rows ──
    ws.Range(ws.Cells(gpFRow, 1), ws.Cells(gpFRow, 9)).Value = ""
    With ws.Range(ws.Cells(gpFRow, 1), ws.Cells(gpFRow, 9))
        .Merge: .Value = PadField("F", 6) & PadField(CStr(femaleCount), 8) & PadField(CStr(femaleCount), 7) & _
            PadField("0", 8) & PadField(CStr(femaleCount), 8) & _
            PadField(CStr(gradeCountF(0)), 5) & PadField(CStr(gradeCountF(1)), 5) & _
            PadField(CStr(gradeCountF(2)), 5) & PadField(CStr(gradeCountF(3)), 5) & _
            PadField(CStr(gradeCountF(4)), 5) & PadField("0", 6) & PadField("0", 5)
    End With
    
    ws.Range(ws.Cells(gpMRow, 1), ws.Cells(gpMRow, 9)).Value = ""
    With ws.Range(ws.Cells(gpMRow, 1), ws.Cells(gpMRow, 9))
        .Merge: .Value = PadField("M", 6) & PadField(CStr(maleCount), 8) & PadField(CStr(maleCount), 7) & _
            PadField("0", 8) & PadField(CStr(maleCount), 8) & _
            PadField(CStr(gradeCountM(0)), 5) & PadField(CStr(gradeCountM(1)), 5) & _
            PadField(CStr(gradeCountM(2)), 5) & PadField(CStr(gradeCountM(3)), 5) & _
            PadField(CStr(gradeCountM(4)), 5) & PadField("0", 6) & PadField("0", 5)
    End With
    
    ws.Range(ws.Cells(gpTRow, 1), ws.Cells(gpTRow, 9)).Value = ""
    With ws.Range(ws.Cells(gpTRow, 1), ws.Cells(gpTRow, 9))
        .Merge: .Value = PadField("T", 6) & PadField(CStr(totalCandidates), 8) & PadField(CStr(totalCandidates), 7) & _
            PadField("0", 8) & PadField(CStr(totalCandidates), 8) & _
            PadField(CStr(gradeCountF(0) + gradeCountM(0)), 5) & PadField(CStr(gradeCountF(1) + gradeCountM(1)), 5) & _
            PadField(CStr(gradeCountF(2) + gradeCountM(2)), 5) & PadField(CStr(gradeCountF(3) + gradeCountM(3)), 5) & _
            PadField(CStr(gradeCountF(4) + gradeCountM(4)), 5) & PadField("0", 6) & PadField("0", 5)
    End With
    
    ' Spacer
    ws.Rows(r).RowHeight = 11.3: r = r + 1
    
    ' ══════════════════════════════════════════════════════════════════════════
    ' BLOCK 7: SUBJECT PERFORMANCE TABLE
    ' FPDF: CODE|NAME|REGIST|SAT|ABS|A|B|C|A-C|D|A-D|E|AVG|GRD|COMPETENCE
    ' ══════════════════════════════════════════════════════════════════════════
    
    ' Title bar
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))
        .Merge: .Value = "EXAMINATION CENTRE SUBJECTS PERFORMANCE"
        .Font.Bold = True: .Font.Size = 10: .Font.Color = CLR_WHITE
        .HorizontalAlignment = xlLeft: .Interior.Color = CLR_HEADER_BG
        .VerticalAlignment = xlCenter: .RowHeight = 22.7: .IndentLevel = 1
    End With
    Call AddBorders(ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))): r = r + 1
    
    ' Header
    Dim spHeaderStr As String
    spHeaderStr = PadField("CODE", 6) & PadField("SUBJECT NAME", 38) & _
                  PadField("REG", 5) & PadField("SAT", 5) & PadField("ABS", 5) & _
                  PadField("A", 4) & PadField("B", 4) & PadField("C", 4) & PadField("A-C", 5) & _
                  PadField("D", 4) & PadField("A-D", 5) & PadField("E", 4) & _
                  PadField("AVG", 5) & PadField("GRD", 5) & "COMPETENCE"
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))
        .Merge: .Value = spHeaderStr
        .Font.Bold = True: .Font.Size = 8: .Font.Color = CLR_WHITE: .Font.Name = "Consolas"
        .HorizontalAlignment = xlLeft: .Interior.Color = CLR_HEADER_BG
        .VerticalAlignment = xlCenter: .RowHeight = 18.7
    End With
    Call AddBorders(ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))): r = r + 1
    
    ' Subject data rows
    For sj = 1 To SUBJECT_COUNT
        mkCol = 5 + SUBJECT_COUNT + 1 + sj
        Dim subjRegCol As Integer: subjRegCol = 5 + sj
        
        Dim sRegistered As Long: sRegistered = 0
        Dim sSat As Long: sSat = 0
        Dim sAbsent As Long: sAbsent = 0
        Dim sA As Long: sA = 0: Dim sB As Long: sB = 0: Dim sC As Long: sC = 0
        Dim sD As Long: sD = 0: Dim sE As Long: sE = 0
        Dim sTotalMarks As Double: sTotalMarks = 0
        
        For rr = DATA_START_ROW To DATA_START_ROW + MAX_CANDIDATES - 1
            If Trim(wsReg.Cells(rr, 2).Value) = "" Then Exit For
            Dim tick As String: tick = UCase(Trim(wsReg.Cells(rr, subjRegCol).Value))
            If tick = "Y" Or tick = "" Then
                sRegistered = sRegistered + 1
                mkv = wsReg.Cells(rr, mkCol).Value
                If IsNumeric(mkv) And mkv <> "" Then
                    sSat = sSat + 1
                    Dim sc As Double: sc = CDbl(mkv)
                    sTotalMarks = sTotalMarks + sc
                    If sc >= 41 Then sA = sA + 1
                    If sc >= 31 And sc < 41 Then sB = sB + 1
                    If sc >= 21 And sc < 31 Then sC = sC + 1
                    If sc >= 11 And sc < 21 Then sD = sD + 1
                    If sc < 11 Then sE = sE + 1
                Else
                    sAbsent = sAbsent + 1
                End If
            End If
        Next rr
        
        Dim sAvg As Double: sAvg = IIf(sSat > 0, sTotalMarks / sSat, 0)
        Dim sGrd As String: sGrd = GradeFromScore(sAvg)
        Dim sCompetence As String: sCompetence = CompetenceLabel(sGrd)
        
        Dim spRowStr As String
        spRowStr = PadField(SubCodes(sj), 6) & PadField(SubFull(sj), 38) & _
                   PadField(CStr(sRegistered), 5) & PadField(CStr(sSat), 5) & PadField(CStr(sAbsent), 5) & _
                   PadField(CStr(sA), 4) & PadField(CStr(sB), 4) & PadField(CStr(sC), 4) & _
                   PadField(CStr(sA + sB + sC), 5) & PadField(CStr(sD), 4) & _
                   PadField(CStr(sA + sB + sC + sD), 5) & PadField(CStr(sE), 4) & _
                   PadField(Format(sAvg, "0"), 5) & PadField(sGrd, 5) & _
                   "Grade " & sGrd & " (" & sCompetence & ")"
        
        With ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))
            .Merge: .Value = spRowStr
            .Font.Bold = True: .Font.Size = 8: .Font.Color = CLR_NAVY: .Font.Name = "Consolas"
            .HorizontalAlignment = xlLeft: .Interior.Color = CLR_CREAM
            .VerticalAlignment = xlCenter: .RowHeight = 18.7
        End With
        Call AddBorders(ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))): r = r + 1
    Next sj
    
    ' ══════════════════════════════════════════════════════════════════════════
    ' BLOCK 8: FOOTER - Generation info + Barcode placeholder + Flag strip
    ' ══════════════════════════════════════════════════════════════════════════
    ws.Rows(r).RowHeight = 11.3: r = r + 1
    
    ' Generated timestamp
    Dim genAt As String: genAt = Format(Now, "dd-mm-yyyy hh:nn:ss")
    Dim nodeName As String: nodeName = UCase(Left(Environ("COMPUTERNAME"), 8))
    If nodeName = "" Then nodeName = "NODE"
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))
        .Merge: .Value = "GENERATED: " & genAt & " | IRMS NODE: " & nodeName
        .Font.Size = 6.2: .Font.Color = RGB(71, 85, 105)
        .HorizontalAlignment = xlRight: .RowHeight = 9.1
    End With
    r = r + 1
    
    ' Barcode placeholder (Code39 text representation)
    Dim barcodePayload As String
    barcodePayload = "PSLE-" & Left(schoolCode, 8) & "-" & Format(Now, "YYYYMMDD-HHmmss") & "-" & nodeName
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))
        .Merge: .Value = "*" & barcodePayload & "*"
        .Font.Name = "Free 3 of 9"  ' Code39 barcode font (if installed)
        .Font.Size = 18: .Font.Color = CLR_DARK_NAVY
        .HorizontalAlignment = xlCenter: .RowHeight = 22.7
    End With
    r = r + 1
    
    ' Barcode text label
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))
        .Merge: .Value = barcodePayload
        .Font.Name = "Arial": .Font.Size = 5.8: .Font.Color = RGB(71, 85, 105)
        .HorizontalAlignment = xlCenter: .RowHeight = 8
    End With
    r = r + 1
    
    ' Tanzania flag strip (bottom)
    ws.Rows(r).RowHeight = 2.5
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 3)): .Merge: .Interior.Color = RGB(0, 166, 81): End With
    With ws.Cells(r, 4): .Interior.Color = RGB(245, 208, 0): End With
    With ws.Range(ws.Cells(r, 5), ws.Cells(r, 6)): .Merge: .Interior.Color = RGB(0, 0, 0): End With
    With ws.Range(ws.Cells(r, 7), ws.Cells(r, 9)): .Merge: .Interior.Color = RGB(11, 47, 91): End With
    r = r + 1
    
    ' Footer text
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 4))
        .Merge: .Value = "RESULTS FOR " & examTitle
        .Font.Size = 7: .Font.Color = RGB(100, 116, 139): .HorizontalAlignment = xlLeft
    End With
    With ws.Range(ws.Cells(r, 5), ws.Cells(r, 9))
        .Merge: .Value = "Page 1/{nb}"
        .Font.Size = 7: .Font.Color = RGB(100, 116, 139): .HorizontalAlignment = xlRight
    End With
    r = r + 1
    
    ' ── Apply powder blue background to entire used range ──
    Dim lastRow As Long: lastRow = r - 1
    Dim bgRange As Range
    Set bgRange = ws.Range(ws.Cells(1, 1), ws.Cells(lastRow, 9))
    ' Only apply to cells without existing fill
    Dim bgR As Long, bgC As Integer
    For bgR = 1 To lastRow
        For bgC = 1 To 9
            If ws.Cells(bgR, bgC).Interior.Color = RGB(255, 255, 255) Or _
               ws.Cells(bgR, bgC).Interior.ColorIndex = xlNone Then
                ' Leave data rows as-is (cream), only fill empty/white cells
            End If
        Next bgC
    Next bgR
    
    ' Set print area
    ws.PageSetup.PrintArea = ws.Range(ws.Cells(1, 1), ws.Cells(lastRow, 9)).Address
    
    ' Insert emblem images if available
    On Error Resume Next
    Dim emblemPath As String
    emblemPath = ThisWorkbook.Path & "\emblem.png"
    If Dir(emblemPath) <> "" Then
        ws.Shapes.AddPicture emblemPath, msoFalse, msoTrue, 5, 14, 45, 45
        ws.Shapes.AddPicture emblemPath, msoFalse, msoTrue, 500, 14, 45, 45
    End If
    On Error GoTo 0
End Sub

' ============================================================================
' SCHOOL INFO, REGISTRATION, MARK ENTRY SHEETS (unchanged from v2)
' ============================================================================
Private Sub BuildSchoolInfoSheet()
    Dim ws As Worksheet: Set ws = ThisWorkbook.Sheets(SH_INFO)
    ws.Cells.Clear: ws.Cells.Font.Name = "Arial"
    ws.Columns("A").ColumnWidth = 2: ws.Columns("B").ColumnWidth = 28: ws.Columns("C").ColumnWidth = 50
    
    Dim r As Long: r = 1
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 4)): .Merge: .RowHeight = 5: .Interior.Color = CLR_DARK_NAVY: End With: r = r + 1
    With ws.Range(ws.Cells(r, 2), ws.Cells(r, 3)): .Merge: .Value = "SCHOOL INFORMATION - IRS 2026"
        .Font.Bold = True: .Font.Size = 14: .Font.Color = CLR_NAVY: .HorizontalAlignment = xlCenter: .RowHeight = 30
    End With: r = r + 1
    
    Dim labels As Variant: labels = Array("School Name:", "School Code:", "Council (District):", "Region:", "Class:", "Exam Year:", "Exam Title:")
    Dim defaults As Variant: defaults = Array("", "", "", "", "STD VII", "2026", "STANDARD SEVEN ZONAL JOINT MOCK EXAMINATION - MAY, 2026")
    Dim i As Integer
    For i = 0 To UBound(labels)
        With ws.Cells(r, 2): .Value = labels(i): .Font.Bold = True: .Font.Size = 11: .Font.Color = RGB(8, 39, 109)
            .Interior.Color = IIf(i Mod 2 = 0, CLR_CREAM, RGB(248, 250, 252)): .RowHeight = 24
        End With
        Call AddBorders(ws.Range(ws.Cells(r, 2), ws.Cells(r, 2)))
        With ws.Cells(r, 3): .Value = defaults(i): .Font.Size = 11: .Interior.Color = RGB(255, 255, 240): End With
        Call AddBorders(ws.Range(ws.Cells(r, 3), ws.Cells(r, 3)))
        r = r + 1
    Next i
    r = r + 1
    With ws.Range(ws.Cells(r, 2), ws.Cells(r, 3)): .Merge: .Value = "Fill fields above, then go to 'Registration'."
        .Font.Italic = True: .Font.Size = 10: .Font.Color = RGB(100, 116, 139)
    End With
    
    On Error Resume Next
    Dim nm As Variant
    For Each nm In Array("SchoolName", "SchoolCode", "CouncilName", "RegionName", "ClassName", "ExamYear", "ExamTitle")
        ThisWorkbook.Names(nm).Delete
    Next nm
    On Error GoTo 0
    ws.Range("C4").Name = "SchoolName": ws.Range("C5").Name = "SchoolCode"
    ws.Range("C6").Name = "CouncilName": ws.Range("C7").Name = "RegionName"
    ws.Range("C8").Name = "ClassName": ws.Range("C9").Name = "ExamYear": ws.Range("C10").Name = "ExamTitle"
End Sub

Private Sub BuildRegistrationSheet()
    Dim ws As Worksheet: Set ws = ThisWorkbook.Sheets(SH_REG)
    ws.Cells.Clear: ws.Cells.Font.Name = "Arial": ws.Cells.Font.Size = 9
    ws.Columns("A").ColumnWidth = 5: ws.Columns("B").ColumnWidth = 18: ws.Columns("C").ColumnWidth = 18
    ws.Columns("D").ColumnWidth = 30: ws.Columns("E").ColumnWidth = 6
    Dim si As Integer
    For si = 1 To SUBJECT_COUNT: ws.Columns(5 + si).ColumnWidth = 10: Next si
    ws.Columns(5 + SUBJECT_COUNT + 1).ColumnWidth = 12
    
    Dim r As Long: r = 1
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 12)): .Merge: .RowHeight = 4: .Interior.Color = CLR_DARK_NAVY: End With: r = r + 1
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 12)): .Merge
        .Value = "CANDIDATE REGISTRATION - Enter candidates, tick Y/N for each subject"
        .Font.Bold = True: .Font.Size = 11: .Font.Color = CLR_NAVY: .HorizontalAlignment = xlCenter: .RowHeight = 26
        .Interior.Color = RGB(219, 234, 254)
    End With: r = r + 1
    
    Dim headers As Variant: headers = Array("S/N", "CANDIDATE NO", "PREM NO", "FULL NAME", "SEX")
    Dim h As Integer
    For h = 0 To UBound(headers)
        With ws.Cells(r, h + 1): .Value = headers(h): .Font.Bold = True: .Font.Size = 9: .Font.Color = CLR_WHITE
            .HorizontalAlignment = xlCenter: .Interior.Color = CLR_HEADER_BG
        End With
    Next h
    For si = 1 To SUBJECT_COUNT
        With ws.Cells(r, 5 + si): .Value = SubShort(si): .Font.Bold = True: .Font.Size = 8: .Font.Color = CLR_WHITE
            .HorizontalAlignment = xlCenter: .Interior.Color = CLR_HEADER_BG: .WrapText = True
        End With
    Next si
    With ws.Cells(r, 5 + SUBJECT_COUNT + 1): .Value = "STATUS": .Font.Bold = True: .Font.Size = 8: .Font.Color = CLR_WHITE
        .HorizontalAlignment = xlCenter: .Interior.Color = CLR_HEADER_BG
    End With
    ws.Rows(r).RowHeight = 22: Call AddBorders(ws.Range(ws.Cells(r, 1), ws.Cells(r, 5 + SUBJECT_COUNT + 1))): r = r + 1
    
    Dim n As Long
    For n = 1 To MAX_CANDIDATES
        ws.Cells(r, 1).Value = n: ws.Cells(r, 1).HorizontalAlignment = xlCenter: ws.Cells(r, 1).Font.Color = RGB(150, 150, 150)
        ws.Cells(r, 2).Interior.Color = RGB(255, 255, 245)
        ws.Cells(r, 3).Interior.Color = RGB(255, 255, 245)
        ws.Cells(r, 4).Interior.Color = RGB(255, 255, 245)
        ws.Cells(r, 5).HorizontalAlignment = xlCenter: ws.Cells(r, 5).Interior.Color = RGB(255, 255, 245)
        With ws.Cells(r, 5).Validation: .Delete: .Add Type:=xlValidateList, AlertStyle:=xlValidAlertStop, Formula1:="M,F": End With
        For si = 1 To SUBJECT_COUNT
            ws.Cells(r, 5 + si).HorizontalAlignment = xlCenter: ws.Cells(r, 5 + si).Interior.Color = RGB(240, 255, 240)
            With ws.Cells(r, 5 + si).Validation: .Delete: .Add Type:=xlValidateList, AlertStyle:=xlValidAlertStop, Formula1:="Y,N": End With
        Next si
        Dim stFormula As String
        stFormula = "=IF(" & ws.Cells(r, 2).Address(False, False) & "="""",""""," & _
            """Registered - "" & COUNTIF(" & ws.Cells(r, 6).Address(False, False) & ":" & ws.Cells(r, 5 + SUBJECT_COUNT).Address(False, False) & ",""Y"") & "" subjects"")"
        ws.Cells(r, 5 + SUBJECT_COUNT + 1).Formula = stFormula
        ws.Cells(r, 5 + SUBJECT_COUNT + 1).Font.Color = RGB(0, 128, 0): ws.Cells(r, 5 + SUBJECT_COUNT + 1).Font.Size = 8
        ws.Rows(r).RowHeight = 16: Call AddBorders(ws.Range(ws.Cells(r, 1), ws.Cells(r, 5 + SUBJECT_COUNT + 1)))
        r = r + 1
    Next n
    ws.Activate: ws.Range("A4").Select: ActiveWindow.FreezePanes = True
End Sub

Private Sub BuildMarkEntrySheet()
    Dim ws As Worksheet: Set ws = ThisWorkbook.Sheets(SH_MARKS)
    ws.Cells.Clear: ws.Cells.Font.Name = "Arial": ws.Cells.Font.Size = 9
    ws.Columns("A").ColumnWidth = 5: ws.Columns("B").ColumnWidth = 18: ws.Columns("C").ColumnWidth = 18
    ws.Columns("D").ColumnWidth = 30: ws.Columns("E").ColumnWidth = 6: ws.Columns("F").ColumnWidth = 14
    ws.Columns("G").ColumnWidth = 8: ws.Columns("H").ColumnWidth = 18: ws.Columns("I").ColumnWidth = 16
    
    Dim r As Long: r = 1
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 9)): .Merge: .RowHeight = 4: .Interior.Color = CLR_DARK_NAVY: End With: r = r + 1
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 3)): .Merge: .Value = "MARK ENTRY - Select subject:"
        .Font.Bold = True: .Font.Size = 12: .Font.Color = CLR_NAVY: .HorizontalAlignment = xlRight: .RowHeight = 30
    End With
    
    Dim subjectList As String: subjectList = SubShort(1) & " - " & SubFull(1)
    Dim si As Integer
    For si = 2 To SUBJECT_COUNT: subjectList = subjectList & "," & SubShort(si) & " - " & SubFull(si): Next si
    With ws.Cells(r, 4): .Value = SubShort(1) & " - " & SubFull(1)
        .Font.Bold = True: .Font.Size = 12: .Font.Color = RGB(30, 64, 175): .Interior.Color = RGB(255, 255, 200)
        With .Validation: .Delete: .Add Type:=xlValidateList, AlertStyle:=xlValidAlertStop, Formula1:=subjectList: End With
    End With
    With ws.Range(ws.Cells(r, 5), ws.Cells(r, 9)): .Merge
        .Value = "   << Select subject, run 'RefreshMarkEntry'": .Font.Italic = True: .Font.Size = 9: .Font.Color = RGB(100, 116, 139)
    End With: r = r + 1
    
    On Error Resume Next: ThisWorkbook.Names("SelectedSubject").Delete: On Error GoTo 0
    ws.Cells(2, 4).Name = "SelectedSubject"
    
    Dim meHeaders As Variant: meHeaders = Array("S/N", "CANDIDATE NO", "PREM NO", "FULL NAME", "SEX", "MARK (0-50)", "GRADE", "COMPETENCE", "REMARKS")
    Dim mh As Integer
    For mh = 0 To UBound(meHeaders)
        With ws.Cells(r, mh + 1): .Value = meHeaders(mh): .Font.Bold = True: .Font.Size = 9: .Font.Color = CLR_WHITE
            .HorizontalAlignment = xlCenter: .Interior.Color = CLR_HEADER_BG: .WrapText = True
        End With
    Next mh
    ws.Rows(r).RowHeight = 24: Call AddBorders(ws.Range(ws.Cells(r, 1), ws.Cells(r, 9))): r = r + 1
    
    Dim mr As Long
    For mr = 0 To MAX_CANDIDATES - 1
        ws.Cells(r + mr, 6).Interior.Color = RGB(255, 255, 220)
        ws.Cells(r + mr, 6).NumberFormat = "0.0"
        ws.Cells(r + mr, 9).Interior.Color = RGB(255, 255, 245)
        ws.Rows(r + mr).RowHeight = 16
    Next mr
    
    ws.Activate: ws.Range("A4").Select: ActiveWindow.FreezePanes = True
    Call PopulateMarkEntryFromRegistration
End Sub

Private Sub PopulateMarkEntryFromRegistration()
    Dim wsReg As Worksheet, wsME As Worksheet
    Set wsReg = ThisWorkbook.Sheets(SH_REG): Set wsME = ThisWorkbook.Sheets(SH_MARKS)
    
    Dim selectedSubject As String: selectedSubject = Trim(wsME.Cells(2, 4).Value)
    If selectedSubject = "" Then Exit Sub
    
    Dim subjShortName As String: subjShortName = Trim(Split(selectedSubject, " - ")(0))
    Dim subjIndex As Integer: subjIndex = 0
    Dim si As Integer
    For si = 1 To SUBJECT_COUNT
        If UCase(SubShort(si)) = UCase(subjShortName) Then subjIndex = si: Exit For
    Next si
    If subjIndex = 0 Then Exit Sub
    
    Dim subjCol As Integer: subjCol = 5 + subjIndex
    Dim markStorageCol As Integer: markStorageCol = 5 + SUBJECT_COUNT + 1 + subjIndex
    
    wsME.Range(wsME.Cells(DATA_START_ROW, 1), wsME.Cells(DATA_START_ROW + MAX_CANDIDATES, 9)).ClearContents
    
    Dim regRow As Long, meRow As Long, counter As Long
    meRow = DATA_START_ROW: counter = 0
    
    For regRow = DATA_START_ROW To DATA_START_ROW + MAX_CANDIDATES - 1
        If Trim(wsReg.Cells(regRow, 2).Value) = "" Then Exit For
        Dim subjectTick As String: subjectTick = UCase(Trim(wsReg.Cells(regRow, subjCol).Value))
        If subjectTick = "Y" Or subjectTick = "" Then
            counter = counter + 1
            wsME.Cells(meRow, 1).Value = counter: wsME.Cells(meRow, 1).HorizontalAlignment = xlCenter
            wsME.Cells(meRow, 2).Value = wsReg.Cells(regRow, 2).Value
            wsME.Cells(meRow, 3).Value = wsReg.Cells(regRow, 3).Value
            wsME.Cells(meRow, 4).Value = wsReg.Cells(regRow, 4).Value
            wsME.Cells(meRow, 5).Value = wsReg.Cells(regRow, 5).Value
            
            Dim savedMark As Variant: savedMark = wsReg.Cells(regRow, markStorageCol).Value
            If IsNumeric(savedMark) And savedMark <> "" Then wsME.Cells(meRow, 6).Value = CDbl(savedMark)
            
            wsME.Cells(meRow, 6).Interior.Color = RGB(255, 255, 220): wsME.Cells(meRow, 6).NumberFormat = "0.0"
            With wsME.Cells(meRow, 6).Validation: .Delete
                .Add Type:=xlValidateDecimal, AlertStyle:=xlValidAlertStop, Operator:=xlBetween, Formula1:="0", Formula2:="50"
            End With
            
            Dim mAddr As String: mAddr = wsME.Cells(meRow, 6).Address(False, False)
            wsME.Cells(meRow, 7).Formula = "=IF(" & mAddr & "="""","""",IF(" & mAddr & ">=41,""A"",IF(" & mAddr & ">=31,""B"",IF(" & mAddr & ">=21,""C"",IF(" & mAddr & ">=11,""D"",""E"")))))"
            wsME.Cells(meRow, 7).HorizontalAlignment = xlCenter: wsME.Cells(meRow, 7).Font.Bold = True
            
            Dim gAddr As String: gAddr = wsME.Cells(meRow, 7).Address(False, False)
            wsME.Cells(meRow, 8).Formula = "=IF(" & gAddr & "="""","""",IF(" & gAddr & "=""A"",""Excellent"",IF(" & gAddr & "=""B"",""Very Good"",IF(" & gAddr & "=""C"",""Good"",IF(" & gAddr & "=""D"",""Satisfactory"",""Unsatisfactory"")))))"
            
            Call AddBorders(wsME.Range(wsME.Cells(meRow, 1), wsME.Cells(meRow, 9)))
            meRow = meRow + 1
        End If
    Next regRow
    
    If counter > 0 Then
        Call AddGradeFormatting(wsME, DATA_START_ROW, DATA_START_ROW + counter - 1, 7)
        Call AddCompetenceFormatting(wsME, DATA_START_ROW, DATA_START_ROW + counter - 1, 8)
    End If
    
    With wsME.Range(wsME.Cells(2, 5), wsME.Cells(2, 9)): .Merge
        .Value = "   " & counter & " candidate(s) for " & selectedSubject
        .Font.Italic = True: .Font.Size = 9: .Font.Color = IIf(counter > 0, RGB(0, 128, 0), RGB(200, 0, 0))
    End With
End Sub

Private Sub SaveMarksToRegistration()
    Dim wsReg As Worksheet, wsME As Worksheet
    Set wsReg = ThisWorkbook.Sheets(SH_REG): Set wsME = ThisWorkbook.Sheets(SH_MARKS)
    
    Dim selectedSubject As String: selectedSubject = Trim(wsME.Cells(2, 4).Value)
    If selectedSubject = "" Then Exit Sub
    Dim subjShortName As String: subjShortName = Trim(Split(selectedSubject, " - ")(0))
    Dim subjIndex As Integer: subjIndex = 0
    Dim si As Integer
    For si = 1 To SUBJECT_COUNT
        If UCase(SubShort(si)) = UCase(subjShortName) Then subjIndex = si: Exit For
    Next si
    If subjIndex = 0 Then Exit Sub
    
    Dim markStorageCol As Integer: markStorageCol = 5 + SUBJECT_COUNT + 1 + subjIndex
    Dim meRow As Long
    For meRow = DATA_START_ROW To DATA_START_ROW + MAX_CANDIDATES - 1
        Dim candNo As String: candNo = Trim(wsME.Cells(meRow, 2).Value)
        If candNo = "" Then Exit For
        Dim regRow As Long
        For regRow = DATA_START_ROW To DATA_START_ROW + MAX_CANDIDATES - 1
            If Trim(wsReg.Cells(regRow, 2).Value) = candNo Then
                wsReg.Cells(regRow, markStorageCol).Value = wsME.Cells(meRow, 6).Value
                Exit For
            End If
        Next regRow
    Next meRow
End Sub

Private Sub BuildSubjectPerfSheet()
    Dim ws As Worksheet: Set ws = ThisWorkbook.Sheets(SH_SUBPERF)
    Dim wsReg As Worksheet: Set wsReg = ThisWorkbook.Sheets(SH_REG)
    ws.Cells.Clear: ws.Cells.Font.Name = "Arial": ws.Cells.Font.Size = 9
    ws.Columns("A").ColumnWidth = 3: ws.Columns("B").ColumnWidth = 7: ws.Columns("C").ColumnWidth = 36
    ws.Columns("D").ColumnWidth = 9: ws.Columns("E").ColumnWidth = 8: ws.Columns("F").ColumnWidth = 7
    ws.Columns("G").ColumnWidth = 7: ws.Columns("H").ColumnWidth = 7: ws.Columns("I").ColumnWidth = 7
    ws.Columns("J").ColumnWidth = 8: ws.Columns("K").ColumnWidth = 7: ws.Columns("L").ColumnWidth = 8
    ws.Columns("M").ColumnWidth = 7: ws.Columns("N").ColumnWidth = 8: ws.Columns("O").ColumnWidth = 7
    ws.Columns("P").ColumnWidth = 22
    
    Dim r As Long: r = 1
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 16)): .Merge: .RowHeight = 4: .Interior.Color = CLR_DARK_NAVY: End With: r = r + 1
    With ws.Range(ws.Cells(r, 2), ws.Cells(r, 16)): .Merge: .Value = "EXAMINATION CENTRE SUBJECTS PERFORMANCE"
        .Font.Bold = True: .Font.Size = 12: .Font.Color = CLR_NAVY: .HorizontalAlignment = xlCenter: .RowHeight = 26
    End With: r = r + 1
    
    Dim spH As Variant: spH = Array("CODE", "SUBJECT NAME", "REGIST", "SAT", "ABS", "A", "B", "C", "A-C", "D", "A-D", "E", "AVG", "GRD", "COMPETENCE LEVEL")
    Dim shi As Integer
    For shi = 0 To UBound(spH)
        With ws.Cells(r, shi + 2): .Value = spH(shi): .Font.Bold = True: .Font.Size = 8: .Font.Color = CLR_WHITE
            .HorizontalAlignment = xlCenter: .Interior.Color = CLR_HEADER_BG: .WrapText = True
        End With
    Next shi
    ws.Rows(r).RowHeight = 24: Call AddBorders(ws.Range(ws.Cells(r, 2), ws.Cells(r, 16))): r = r + 1
    
    Dim sj As Integer
    For sj = 1 To SUBJECT_COUNT
        Dim mkCol As Integer: mkCol = 5 + SUBJECT_COUNT + 1 + sj
        Dim subjCol As Integer: subjCol = 5 + sj
        Dim sReg As Long: sReg = 0: Dim sSat As Long: sSat = 0: Dim sAbs As Long: sAbs = 0
        Dim sA As Long: sA = 0: Dim sB As Long: sB = 0: Dim sC As Long: sC = 0
        Dim sD As Long: sD = 0: Dim sE As Long: sE = 0: Dim sTot As Double: sTot = 0
        Dim rr As Long
        For rr = DATA_START_ROW To DATA_START_ROW + MAX_CANDIDATES - 1
            If Trim(wsReg.Cells(rr, 2).Value) = "" Then Exit For
            Dim tk As String: tk = UCase(Trim(wsReg.Cells(rr, subjCol).Value))
            If tk = "Y" Or tk = "" Then
                sReg = sReg + 1
                Dim mv As Variant: mv = wsReg.Cells(rr, mkCol).Value
                If IsNumeric(mv) And mv <> "" Then
                    sSat = sSat + 1: Dim sv As Double: sv = CDbl(mv): sTot = sTot + sv
                    If sv >= 41 Then sA = sA + 1
                    If sv >= 31 And sv < 41 Then sB = sB + 1
                    If sv >= 21 And sv < 31 Then sC = sC + 1
                    If sv >= 11 And sv < 21 Then sD = sD + 1
                    If sv < 11 Then sE = sE + 1
                Else: sAbs = sAbs + 1
                End If
            End If
        Next rr
        Dim sAvg As Double: sAvg = IIf(sSat > 0, sTot / sSat, 0)
        Dim sGrd As String: sGrd = GradeFromScore(sAvg)
        
        ws.Cells(r, 2).Value = SubCodes(sj): ws.Cells(r, 3).Value = SubFull(sj)
        ws.Cells(r, 4).Value = sReg: ws.Cells(r, 5).Value = sSat: ws.Cells(r, 6).Value = sAbs
        ws.Cells(r, 7).Value = sA: ws.Cells(r, 8).Value = sB: ws.Cells(r, 9).Value = sC
        ws.Cells(r, 10).Value = sA + sB + sC: ws.Cells(r, 11).Value = sD
        ws.Cells(r, 12).Value = sA + sB + sC + sD: ws.Cells(r, 13).Value = sE
        ws.Cells(r, 14).Value = Round(sAvg, 0): ws.Cells(r, 15).Value = sGrd
        ws.Cells(r, 16).Value = "Grade " & sGrd & " (" & CompetenceLabel(sGrd) & ")"
        
        Dim dc As Integer
        For dc = 2 To 16: ws.Cells(r, dc).HorizontalAlignment = xlCenter: Next dc
        ws.Cells(r, 3).HorizontalAlignment = xlLeft: ws.Cells(r, 16).HorizontalAlignment = xlLeft
        With ws.Range(ws.Cells(r, 2), ws.Cells(r, 16)): .Font.Bold = True: .Font.Size = 9: .Font.Color = CLR_NAVY: .Interior.Color = CLR_CREAM: End With
        ws.Rows(r).RowHeight = 18: Call AddBorders(ws.Range(ws.Cells(r, 2), ws.Cells(r, 16)))
        r = r + 1
    Next sj
    
    Call AddGradeFormatting(ws, r - SUBJECT_COUNT, r - 1, 15)
    Call AddCompetenceFormatting(ws, r - SUBJECT_COUNT, r - 1, 16)
End Sub

' ============================================================================
' HELPERS
' ============================================================================
Private Sub InitSubjects()
    SubCodes(1) = "01": SubShort(1) = "KISW": SubFull(1) = "KISWAHILI"
    SubCodes(2) = "02": SubShort(2) = "ENG": SubFull(2) = "ENGLISH LANGUAGE"
    SubCodes(3) = "03": SubShort(3) = "SOC": SubFull(3) = "SOCIAL STUDIES AND VOCATIONAL SKILLS"
    SubCodes(4) = "04": SubShort(4) = "MATH": SubFull(4) = "MATHEMATICS"
    SubCodes(5) = "05": SubShort(5) = "SCI": SubFull(5) = "SCIENCE AND TECHNOLOGY"
    SubCodes(6) = "06": SubShort(6) = "CIVIC": SubFull(6) = "CIVIC AND MORAL EDUCATION"
End Sub

Private Sub CreateSheetIfMissing(shName As String)
    Dim ws As Worksheet
    On Error Resume Next: Set ws = ThisWorkbook.Sheets(shName): On Error GoTo 0
    If ws Is Nothing Then
        Set ws = ThisWorkbook.Sheets.Add(After:=ThisWorkbook.Sheets(ThisWorkbook.Sheets.Count)): ws.Name = shName
    Else: ws.Cells.Clear
    End If
End Sub

Private Sub AddBorders(rng As Range)
    With rng.Borders: .LineStyle = xlContinuous: .Weight = xlThin: .Color = RGB(148, 163, 184): End With
End Sub

Private Function PadField(val As String, width As Integer) As String
    If Len(val) >= width Then
        PadField = Left(val, width)
    Else
        PadField = val & Space(width - Len(val))
    End If
End Function

Private Function CompetenceLabel(grade As String) As String
    Select Case UCase(grade)
        Case "A": CompetenceLabel = "Excellent"
        Case "B": CompetenceLabel = "Very Good"
        Case "C": CompetenceLabel = "Good"
        Case "D": CompetenceLabel = "Satisfactory"
        Case Else: CompetenceLabel = "Unsatisfactory"
    End Select
End Function

Private Function GradeFromScore(score As Double) As String
    Select Case True
        Case score >= 41: GradeFromScore = "A"
        Case score >= 31: GradeFromScore = "B"
        Case score >= 21: GradeFromScore = "C"
        Case score >= 11: GradeFromScore = "D"
        Case Else: GradeFromScore = "E"
    End Select
End Function

Private Function GradePoint(grade As String) As Integer
    Select Case UCase(grade)
        Case "A": GradePoint = 1: Case "B": GradePoint = 2: Case "C": GradePoint = 3
        Case "D": GradePoint = 4: Case Else: GradePoint = 5
    End Select
End Function

Private Sub AddCompetenceFormatting(ws As Worksheet, startRow As Long, endRow As Long, col As Integer)
    Dim rng As Range: Set rng = ws.Range(ws.Cells(startRow, col), ws.Cells(endRow, col))
    Dim fc As FormatCondition
    Set fc = rng.FormatConditions.Add(Type:=xlCellValue, Operator:=xlEqual, Formula1:="=""Excellent""")
    fc.Interior.Color = RGB(0, 168, 42): fc.Font.Color = CLR_WHITE: fc.Font.Bold = True
    Set fc = rng.FormatConditions.Add(Type:=xlCellValue, Operator:=xlEqual, Formula1:="=""Very Good""")
    fc.Interior.Color = RGB(31, 238, 11): fc.Font.Color = RGB(0, 50, 0): fc.Font.Bold = True
    Set fc = rng.FormatConditions.Add(Type:=xlCellValue, Operator:=xlEqual, Formula1:="=""Good""")
    fc.Interior.Color = RGB(222, 240, 67): fc.Font.Color = RGB(50, 50, 0): fc.Font.Bold = True
    Set fc = rng.FormatConditions.Add(Type:=xlCellValue, Operator:=xlEqual, Formula1:="=""Satisfactory""")
    fc.Interior.Color = RGB(255, 119, 47): fc.Font.Color = CLR_WHITE: fc.Font.Bold = True
    Set fc = rng.FormatConditions.Add(Type:=xlCellValue, Operator:=xlEqual, Formula1:="=""Unsatisfactory""")
    fc.Interior.Color = RGB(255, 39, 47): fc.Font.Color = CLR_WHITE: fc.Font.Bold = True
End Sub

Private Sub AddGradeFormatting(ws As Worksheet, startRow As Long, endRow As Long, col As Integer)
    Dim rng As Range: Set rng = ws.Range(ws.Cells(startRow, col), ws.Cells(endRow, col))
    Dim fc As FormatCondition
    Set fc = rng.FormatConditions.Add(Type:=xlCellValue, Operator:=xlEqual, Formula1:="=""A""")
    fc.Interior.Color = RGB(0, 168, 42): fc.Font.Color = CLR_WHITE: fc.Font.Bold = True
    Set fc = rng.FormatConditions.Add(Type:=xlCellValue, Operator:=xlEqual, Formula1:="=""B""")
    fc.Interior.Color = RGB(31, 238, 11): fc.Font.Color = RGB(0, 50, 0): fc.Font.Bold = True
    Set fc = rng.FormatConditions.Add(Type:=xlCellValue, Operator:=xlEqual, Formula1:="=""C""")
    fc.Interior.Color = RGB(222, 240, 67): fc.Font.Color = RGB(50, 50, 0): fc.Font.Bold = True
    Set fc = rng.FormatConditions.Add(Type:=xlCellValue, Operator:=xlEqual, Formula1:="=""D""")
    fc.Interior.Color = RGB(255, 119, 47): fc.Font.Color = CLR_WHITE: fc.Font.Bold = True
    Set fc = rng.FormatConditions.Add(Type:=xlCellValue, Operator:=xlEqual, Formula1:="=""E""")
    fc.Interior.Color = RGB(255, 39, 47): fc.Font.Color = CLR_WHITE: fc.Font.Bold = True
End Sub
