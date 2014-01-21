<?php
return array(
	'groups' => array(
		"support_moderator" => "Moderator Support"
	)
,
	'brick' => array(
		'templates' => array(
			"1" => "Message to support \"{v#tl}\"",
			"2" => "
	<p>
		User <b>{v#unm}</b> posted a new message to support <a href='{v#plnk}'>{v#tl}</a>.
	</p>
	<p>Text:</p>
	<blockquote>
		{v#prj}
	</blockquote>
	
	<p>Best regards,<br />
	 {v#sitename}</p>",
			"3" => "New comment in \"{v#tl}\"",
			"4" => "
	<p>
		User <b>{v#unm}</b> wrote a comment to the post 
		<a href='{v#plnk}'>{v#tl}</a>:
	</p>
	<blockquote>{v#cmt}</blockquote>
	<p>Best regards,<br />
	 {v#sitename}</p>",
			"5" => "Response to your comment in \"{v#tl}\"",
			"6" => "
	<p>User <b>{v#unm}</b> replied to your comment in <a href='{v#plnk}'>{v#tl}</a>:</p>
	<blockquote>{v#cmt2}</blockquote>
	<p>Comment:</p>
	<blockquote>{v#cmt1}</blockquote>
	<p>Best regards,<br />
	 {v#sitename}</p>",
			"7" => "New comment in \"{v#tl}\"",
			"8" => "
	<p>User <b>{v#unm}</b> wrote a comment in <a href='{v#plnk}'>{v#tl}</a>:</p>
	<blockquote>{v#cmt}</blockquote>
	<p>Best regards,<br />
	 {v#sitename}</p>"
		)

	)
,
	'content' => array(
		'upload' => array(
			"1" => "Select a file on your computer",
			"2" => "File upload",
			"3" => "Upload file. Please, wait...",
			"4" => "Upload",
			"5" => "Well to load file <b>{v#fname}</b>:",
			"6" => "Unknown file type",
			"7" => "File size exceeds the allowable",
			"8" => "Server error",
			"9" => "Image size exceeds the allowable",
			"10" => "Not enough free space on your profile",
			"11" => "Not authorized to download the file",
			"12" => "File with the same name is already loaded",
			"13" => "You must select a file to download",
			"14" => "Incorrect image"
		)

	)
);
?>