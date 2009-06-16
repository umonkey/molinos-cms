var gconfig = {
	'Авторизация': [{
		path: 'api/auth/form.xml',
		description: 'Возвращает форму авторизации',
		documentation: 'http://code.google.com/p/molinos-cms/wiki/AuthModule#api/auth/form.xml',
		params: null
	}, {
		path: 'api/auth/info.xml',
		description: 'Возвращает описание текущего пользователя.',
		documentation: 'http://code.google.com/p/molinos-cms/wiki/AuthModule#api/auth/info.xml',
		params: null
	}],
	'Комментарии': [{
		path: 'api/comments/list.xml',
		description: 'Возвращает список комментариев к ноде.',
		documentation: 'http://code.google.com/p/molinos-cms/wiki/ApiModule#api/forms/create/types.xml',
		params: {
			'node': 'ID объекта'
		}
	}],
	'Ноды': [{
		path: 'api/node/list.xml',
		description: 'Возвращает список объектов.',
		documentation: 'http://code.google.com/p/molinos-cms/wiki/ApiModule#api/node/list.xml',
		params: {
			'sort': ' порядок сортировки, по умолчанию: "-id"',
			'limit': 'количество возвращаемых элементов, по умолчанию: 10',
			'class': 'список типов объектов, через запятую, например: "article,story". Если не указан, возвращаются объекты любого типа.',
			'tags': ' список идентификаторов разделов, через запятую, например: "10,407". Если не указан, возвращаются объекты из любых разделов.'
		}
	}]
};

/*
 * 'Авторизация': [{
		path: 'api/forms/create/types.xml',
		description: 'Возвращает список типов, доступных пользователю в режиме создания.',
		documentation: 'http://code.google.com/p/molinos-cms/wiki/ApiModule#api/forms/create/types.xml',
		params: {
			'id': 'ID объекта'
		}
	}, {
		path: 'api/node/list.xml',
		description: 'Возвращает список объектов.',
		documentation: 'http://code.google.com/p/molinos-cms/wiki/ApiModule#api/node/list.xml',
		params: {
			'sort': ' порядок сортировки, по умолчанию: "-id"',
			'limit': 'количество возвращаемых элементов, по умолчанию: 10',
			'class': 'список типов объектов, через запятую, например: "article,story". Если не указан, возвращаются объекты любого типа.',
			'tags': ' список идентификаторов разделов, через запятую, например: "10,407". Если не указан, возвращаются объекты из любых разделов.'
		}
	}, {
		path: 'api/auth/form.xml',
		description: 'Возвращает форму авторизации.',
		documentation: 'http://code.google.com/p/molinos-cms/wiki/AuthModule#api/auth/form.xml',
		params: null
	}]
 */
