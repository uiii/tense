function Get-Out {
	param(
		[string] $file
	)

	return (Get-Content $file | select-string -Pattern 'Time' -notmatch | select-string -Patter '\.php:[0-9]' -notmatch)
}

$testDir = $PSScriptRoot

cd (Join-Path $testDir "..\..\..\example")
php ..\tense run > (Join-Path $testDir "current-out.log") 2> $null

cd $testDir
Get-Out .\out.log
Get-Out .\current-out.log
$diff = Compare-Object (Get-Out .\out.log) (Get-Out .\current-out.log)

if ($diff.count -gt 0) {
	$diff
	throw "Different output"
} else {
	echo "Outputs are the same"
}