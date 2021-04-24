@echo off
rem MySQL Backup Script

for /f "usebackq skip=1 tokens=1-6" %%g in (`wmic Path Win32_LocalTime Get Day^,Hour^,Minute^,Month^,Second^,Year ^| findstr /r /v "^$"`) do (
  set _day=00%%g
  set _hours=00%%h
  set _minutes=00%%i
  set _month=00%%j
  set _seconds=00%%k
  set _year=%%l
 )
set _month=%_month:~-2%
set _day=%_day:~-2%
set _hh=%_hours:~-2%
set _mm=%_minutes:~-2%
set _ss=%_seconds:~-2%
set datetime=%_year%%_month%%_day%%_hh%%_mm%%_ss%

set username=root
set password=toor
set database=test

rem https://dev.mysql.com/doc/refman/5.6/en/mysqldump.html
mysqldump --add-drop-table -u%username% -p%password% %database% > %database%-%datetime%.sql.bak
