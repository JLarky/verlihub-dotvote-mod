make: dotvote.lua

dotvote.lua : dotvote.utf
	iconv -t cp1251 dotvote.utf -o dotvote.lua
