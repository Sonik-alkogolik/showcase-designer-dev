@echo off
setlocal
cd /d %~dp0..
python scripts\test_runner_ui.py
endlocal

