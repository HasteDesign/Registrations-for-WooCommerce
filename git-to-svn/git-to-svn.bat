:: Name:     git-to-svn
:: Purpose:  move WordPress git repository structure to svn dir
:: Author:   @allysonsouza
:: URL:      https://github.com/allysonsouza
:: Version:  0.0.1
:: License:  GPL-v2

@ECHO OFF
SETLOCAL ENABLEEXTENSIONS ENABLEDELAYEDEXPANSION

:: variables
SET me=%~n0
SET interactive=0

:: config
SET plugin=registrations-for-woocommerce
SET version=2.0.1
SET svndir=C:\web\svn\%plugin%
SET assets=%svndir%\assets\
SET tag=%svndir%\tags\%version%\
SET trunk=%svndir%\trunk\

ECHO %CMDCMDLINE% | FINDSTR /L %COMSPEC% >NUL 2>&1
IF %ERRORLEVEL% == 0 SET interactive=1

:: assets
xcopy "..\assets\*.png" "%assets%" /y
xcopy "..\assets\*.svg" "%assets%" /y
xcopy "..\assets\*.jpg" "%assets%" /y

:: trunk
xcopy "..\readme.txt" "%trunk%" /y

:: tag
xcopy "..\*" "%tag%" /exclude:ignore.txt /s

IF "%interactive%"=="0" PAUSE
EXIT /B 0
