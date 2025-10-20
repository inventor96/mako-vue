<p>Hey {{$first_name}},</p>
<p>Looks like you indicated that you forgot your password! No worries, we all forget stuff sometimes.</p>
<p>If this was in fact you that made the request, head over to <a href="{{route:'auth:resetPassword', ['token' => $token]}}" target="_blank">{{route:'auth:resetPassword', ['token' => $token]}}</a> to reset your password.</p>
<p>If this was not you, no worries; whoever it was won't get access to your account unless they're able to get to your email.</p>
<p>Sincerely,<br>The Team</p>