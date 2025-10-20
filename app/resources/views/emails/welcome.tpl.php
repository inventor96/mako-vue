<p>Hi {{$first_name}},</p>
<p>Welcome to Mako Vue!</p>
<p>To verify your email, go to <a href="{{route:'auth:activate', ['token' => $token]}}">{{route:'auth:activate', ['token' => $token]}}</a>.</p>
<p>Sincerely,<br>The Team</p>