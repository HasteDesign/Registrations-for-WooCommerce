let mix = require('laravel-mix')

mix.options(
	{
		manifest: false
	}
)

mix.js('app/Settings/index.js','assets/js/settings.js').react()
   .js('app/Product/index.js','assets/js/product.js').react()
   .sass('app/styles/admin-settings.scss','assets/css/admin-settings.css')
   .sass('app/styles/product.scss','assets/css/product.css')
   .setPublicPath('assets/');