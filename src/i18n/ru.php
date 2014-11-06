<?php
return array(
    "title" => "Техподдержка",
    'groups' => array(
        "support_moderator" => "Модератор техподдержки"
    ),
    'brick' => array(
        'templates' => array(
            "1" => "Сообщение в техподдержку \"{v#tl}\"",
            "2" => "
	<p>
		Пользователь <b>{v#unm}</b> опубликовал(а) новое сообщение в техподдержку <a href='{v#plnk}'>{v#tl}</a>.
	</p>
	<p>Текст сообщения:</p>
	<blockquote>
		{v#prj}
	</blockquote>
	
	<p>С наилучшими пожеланиями,<br />
	 {v#sitename}</p>",
            "3" => "Новый комментарий в сообщении \"{v#tl}\"",
            "4" => "
	<p>
		Пользователь <b>{v#unm}</b> написал(а) комментарий к сообщению 
		<a href='{v#plnk}'>{v#tl}</a>:
	</p>
	<blockquote>{v#cmt}</blockquote>
	<p>С наилучшими пожеланиями,<br />
	 {v#sitename}</p>",
            "5" => "Ответ на ваш комментарий в сообщении \"{v#tl}\"",
            "6" => "
	<p>Пользователь <b>{v#unm}</b> ответил(а) на ваш комментарий в сообщении <a href='{v#plnk}'>{v#tl}</a>:</p>
	<blockquote>{v#cmt2}</blockquote>
	<p>Текст комментария:</p>
	<blockquote>{v#cmt1}</blockquote>
	<p>С наилучшими пожеланиями,<br />
	 {v#sitename}</p>",
            "7" => "Новый комментарий в сообщении \"{v#tl}\"",
            "8" => "
	<p>Пользователь <b>{v#unm}</b> написал(а) комментарий в сообщение <a href='{v#plnk}'>{v#tl}</a>:</p>
	<blockquote>{v#cmt}</blockquote>
	<p>С наилучшими пожеланиями,<br />
	 {v#sitename}</p>"
        )

    ),
    'content' => array(
        'upload' => array(
            "1" => "Выберите файл на своем компьютере",
            "2" => "Загрузка файла",
            "3" => "Идет загрузка файла, пожалуйста, подождите...",
            "4" => "Загрузить",
            "5" => "Ну удалось загрузить файл <b>{v#fname}</b>:",
            "6" => "Неизвестный тип файла",
            "7" => "Размер файла превышает допустимый",
            "8" => "Ошибка сервера",
            "9" => "Размер изображения превышает допустимый",
            "10" => "Недостаточно свободного места в вашем профиле",
            "11" => "Нет прав на загрузку файла",
            "12" => "Файл с таким именем уже загружен",
            "13" => "Необходимо выбрать файл для загрузки",
            "14" => "Некорректное изображение"
        )

    )
);
?>