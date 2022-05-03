let mix = require('laravel-mix')

mix.options(
	{
		manifest: false
	}
)

mix.js('app/Settings/index.js','assets/js/settings.js').react()
   .sass('app/styles/admin-settings.scss','assets/css/admin-settings.css')
