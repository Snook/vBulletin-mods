; ########################################################################
;
; gIRChat, Copyright © 2006, Ghryphen (github.com/ghryphen)
;
; If you have fixes, improvements or other additions to make to
; gIRChat, please contact me at ghryphen@gmail.com for collaboration.
; I appreciate your kind consideration.
;
; This work is licensed under the Creative Commons
; Attribution-Noncommercial-No Derivative Works 3.0 United States License.
; To view a copy of this license, visit
; http://creativecommons.org/licenses/by-nc-nd/3.0/us/ or send a letter to
; Creative Commons, 171 Second Street, Suite 300,
; San Francisco, California, 94105, USA.
;
; ########################### SVN INFO ###################################
; $Id: gIRChat.mrc 998 2008-07-02 23:23:43Z ghryphen $
; $Rev: 998 $
; $LastChangedBy: ghryphen $
; $Date: 2008-07-02 16:23:43 -0700 (Wed, 02 Jul 2008) $

alias girchat {
  %girchat.channel = #clan-atf
  %girchat.domain = www.alliedtribalforces.com

  %girchat.total = $nick(%girchat.channel,0)

  %girchat.op =
  var %x = 1
  while (%x <= $nick(%girchat.channel,0,o)) {
    %girchat.op = %girchat.op $+ $nick(%girchat.channel,%x,o) $+ ,
    inc %x
  }

  %girchat.hop =
  %x = 1
  while (%x <= $nick(%girchat.channel,0,h)) {
    %girchat.hop = %girchat.hop $+ $nick(%girchat.channel,%x,h) $+ ,
    inc %x
  }

  %girchat.voice =
  %x = 1
  while (%x <= $nick(%girchat.channel,0,v)) {
    %girchat.voice = %girchat.voice $+ $nick(%girchat.channel,%x,v) $+ ,
    inc %x
  }

  %girchat.normal =
  %x = 1
  while (%x <= $nick(%girchat.channel,0,r)) {
    %girchat.normal = %girchat.normal $+ $nick(%girchat.channel,%x,r) $+ ,
    inc %x
  }

  %girchat.topic = $chan(%girchat.channel).topic

  sockclose girchat
  sockopen girchat %girchat.domain 80
}

on *:sockopen:girchat:{
  var %data = key=1234&action=statusupdate
  %data = %data $+ &channel= $+ %girchat.channel
  %data = %data $+ &op= $+ %girchat.op
  %data = %data $+ &hop= $+ %girchat.hop
  %data = %data $+ &voice= $+ %girchat.voice
  %data = %data $+ &normal= $+ %girchat.normal
  %data = %data $+ &topic= $+ %girchat.topic
  %data = %data $+ &total= $+ %girchat.total

  sockwrite -n $sockname POST /girchat.php HTTP/1.1
  sockwrite -n $sockname Host: %girchat.domain
  sockwrite -n $sockname Content-Type: application/x-www-form-urlencoded
  sockwrite -n $sockname Content-Length: $calc($len(%data)+1)
  sockwrite -n $sockname $crlf %data

  ;echo gIRChat sent an update.
}

on *:TOPIC:%channel:.timergirchat 1 10 girchat
on *:JOIN:%channel:.timergirchat 1 8 girchat
on *:PART:%channel:.timergirchat 1 6 if ( %girchat.total > $!nick(%girchat.channel,0) ) girchat
on *:QUIT:.timergirchat 1 4 if ( %girchat.total > $!nick(%girchat.channel,0) ) girchat
on *:NICK:.timergirchat 1 2 if ( $newnick ison %girchat.channel ) girchat
on *:START:.timergirchatrepeat 0 300 girchat

; end