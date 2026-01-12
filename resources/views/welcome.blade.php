<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any" id="favicon-ico">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml" id="favicon-svg">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles -->
        <style>
            /*! tailwindcss v4.0.14 | MIT License | https://tailwindcss.com */
            @layer theme{:root,:host{--font-sans:ui-sans-serif,system-ui,sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji";--font-mono:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;--color-green-600:oklch(.627 .194 149.214);--color-gray-900:oklch(.21 .034 264.665);--color-zinc-50:oklch(.985 0 0);--color-zinc-200:oklch(.92 .004 286.32);--color-zinc-400:oklch(.705 .015 286.067);--color-zinc-500:oklch(.552 .016 285.938);--color-zinc-600:oklch(.442 .017 285.786);--color-zinc-700:oklch(.37 .013 285.805);--color-zinc-800:oklch(.274 .006 286.033);--color-zinc-900:oklch(.21 .006 285.885);--color-neutral-100:oklch(.97 0 0);--color-neutral-200:oklch(.922 0 0);--color-neutral-700:oklch(.371 0 0);--color-neutral-800:oklch(.269 0 0);--color-neutral-900:oklch(.205 0 0);--color-neutral-950:oklch(.145 0 0);--color-stone-800:oklch(.268 .007 34.298);--color-stone-950:oklch(.147 .004 49.25);--color-black:#000;--color-white:#fff;--spacing:.25rem;--container-sm:24rem;--container-md:28rem;--container-lg:32rem;--container-4xl:56rem;--text-xs:.75rem;--text-xs--line-height:calc(1/.75);--text-sm:.875rem;--text-sm--line-height:calc(1.25/.875);--text-lg:1.125rem;--text-lg--line-height:calc(1.75/1.125);--font-weight-normal:400;--font-weight-medium:500;--font-weight-semibold:600;--leading-tight:1.25;--leading-normal:1.5;--radius-sm:.25rem;--radius-md:.375rem;--radius-lg:.5rem;--radius-xl:.75rem;--aspect-video:16/9;--default-transition-duration:.15s;--default-transition-timing-function:cubic-bezier(.4,0,.2,1);--default-font-family:var(--font-sans);--default-font-feature-settings:var(--font-sans--font-feature-settings);--default-font-variation-settings:var(--font-sans--font-variation-settings);--default-mono-font-family:var(--font-mono);--default-mono-font-feature-settings:var(--font-mono--font-feature-settings);--default-mono-font-variation-settings:var(--font-mono--font-variation-settings)}}@layer base{*,:after,:before,::backdrop{box-sizing:border-box;border:0 solid;margin:0;padding:0}::file-selector-button{box-sizing:border-box;border:0 solid;margin:0;padding:0}html,:host{-webkit-text-size-adjust:100%;tab-size:4;line-height:1.5;font-family:var(--default-font-family,ui-sans-serif,system-ui,sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji");font-feature-settings:var(--default-font-feature-settings,normal);font-variation-settings:var(--default-font-variation-settings,normal);-webkit-tap-highlight-color:transparent}body{line-height:inherit}hr{height:0;color:inherit;border-top-width:1px}abbr:where([title]){-webkit-text-decoration:underline dotted;text-decoration:underline dotted}h1,h2,h3,h4,h5,h6{font-size:inherit;font-weight:inherit}a{color:inherit;-webkit-text-decoration:inherit;-webkit-text-decoration:inherit;-webkit-text-decoration:inherit;text-decoration:inherit}b,strong{font-weight:bolder}code,kbd,samp,pre{font-family:var(--default-mono-font-family,ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace);font-feature-settings:var(--default-mono-font-feature-settings,normal);font-variation-settings:var(--default-mono-font-variation-settings,normal);font-size:1em}small{font-size:80%}sub,sup{vertical-align:baseline;font-size:75%;line-height:0;position:relative}sub{bottom:-.25em}sup{top:-.5em}table{text-indent:0;border-color:inherit;border-collapse:collapse}:-moz-focusring{outline:auto}progress{vertical-align:baseline}summary{display:list-item}ol,ul,menu{list-style:none}img,svg,video,canvas,audio,iframe,embed,object{vertical-align:middle;display:block}img,video{max-width:100%;height:auto}button,input,select,optgroup,textarea{font:inherit;font-feature-settings:inherit;font-variation-settings:inherit;letter-spacing:inherit;color:inherit;opacity:1;background-color:#0000;border-radius:0}::file-selector-button{font:inherit;font-feature-settings:inherit;font-variation-settings:inherit;letter-spacing:inherit;color:inherit;opacity:1;background-color:#0000;border-radius:0}:where(select:is([multiple],[size])) optgroup{font-weight:bolder}:where(select:is([multiple],[size])) optgroup option{padding-inline-start:20px}::file-selector-button{margin-inline-end:4px}::placeholder{opacity:1;color:color-mix(in oklab,currentColor 50%,transparent)}textarea{resize:vertical}::-webkit-search-decoration{-webkit-appearance:none}::-webkit-date-and-time-value{min-height:1lh;text-align:inherit}::-webkit-datetime-edit{display:inline-flex}::-webkit-datetime-edit-fields-wrapper{padding:0}::-webkit-datetime-edit{padding-block:0}::-webkit-datetime-edit-year-field{padding-block:0}::-webkit-datetime-edit-month-field{padding-block:0}::-webkit-datetime-edit-day-field{padding-block:0}::-webkit-datetime-edit-hour-field{padding-block:0}::-webkit-datetime-edit-minute-field{padding-block:0}::-webkit-datetime-edit-second-field{padding-block:0}::-webkit-datetime-edit-millisecond-field{padding-block:0}::-webkit-datetime-edit-meridiem-field{padding-block:0}:-moz-ui-invalid{box-shadow:none}button,input:where([type=button],[type=reset],[type=submit]){appearance:button}::file-selector-button{appearance:button}::-webkit-inner-spin-button{height:auto}::-webkit-outer-spin-button{height:auto}[hidden]:where(:not([hidden=until-found])){display:none!important}}@layer components;@layer utilities{.sr-only{clip:rect(0,0,0,0);white-space:nowrap;border-width:0;width:1px;height:1px;margin:-1px;padding:0;position:absolute;overflow:hidden}.absolute{position:absolute}.relative{position:relative}.static{position:static}.sticky{position:sticky}.inset-0{inset:calc(var(--spacing)*0)}.inset-y-\[3px\]{inset-block:3px}.start-0{inset-inline-start:calc(var(--spacing)*0)}.end-0{inset-inline-end:calc(var(--spacing)*0)}.top-0{top:calc(var(--spacing)*0)}.z-20{z-index:20}.container{width:100%}@media (width>=40rem){.container{max-width:40rem}}@media (width>=48rem){.container{max-width:48rem}}@media (width>=64rem){.container{max-width:64rem}}@media (width>=80rem){.container{max-width:80rem}}@media (width>=96rem){.container{max-width:96rem}}.mx-auto{margin-inline:auto}.my-6{margin-block:calc(var(--spacing)*6)}.-ms-8{margin-inline-start:calc(var(--spacing)*-8)}.ms-1{margin-inline-start:calc(var(--spacing)*1)}.ms-2{margin-inline-start:calc(var(--spacing)*2)}.ms-4{margin-inline-start:calc(var(--spacing)*4)}.me-1\.5{margin-inline-end:calc(var(--spacing)*1.5)}.me-2{margin-inline-end:calc(var(--spacing)*2)}.me-3{margin-inline-end:calc(var(--spacing)*3)}.me-5{margin-inline-end:calc(var(--spacing)*5)}.me-10{margin-inline-end:calc(var(--spacing)*10)}.-mt-\[4\.9rem\]{margin-top:-4.9rem}.mt-2{margin-top:calc(var(--spacing)*2)}.mt-4{margin-top:calc(var(--spacing)*4)}.mt-5{margin-top:calc(var(--spacing)*5)}.mt-6{margin-top:calc(var(--spacing)*6)}.mt-10{margin-top:calc(var(--spacing)*10)}.mt-auto{margin-top:auto}.-mb-px{margin-bottom:-1px}.mb-0\.5{margin-bottom:calc(var(--spacing)*.5)}.mb-1{margin-bottom:calc(var(--spacing)*1)}.mb-2{margin-bottom:calc(var(--spacing)*2)}.mb-4{margin-bottom:calc(var(--spacing)*4)}.mb-5{margin-bottom:calc(var(--spacing)*5)}.mb-6{margin-bottom:calc(var(--spacing)*6)}.mb-\[2px\]{margin-bottom:2px}.block{display:block}.contents{display:contents}.flex{display:flex}.grid{display:grid}.hidden{display:none}.inline-block{display:inline-block}.inline-flex{display:inline-flex}.table{display:table}.aspect-\[335\/376\]{aspect-ratio:335/376}.aspect-square{aspect-ratio:1}.aspect-video{aspect-ratio:var(--aspect-video)}.size-3\!{width:calc(var(--spacing)*3)!important;height:calc(var(--spacing)*3)!important}.size-5{width:calc(var(--spacing)*5);height:calc(var(--spacing)*5)}.size-8{width:calc(var(--spacing)*8);height:calc(var(--spacing)*8)}.size-9{width:calc(var(--spacing)*9);height:calc(var(--spacing)*9)}.size-full{width:100%;height:100%}.\!h-10{height:calc(var(--spacing)*10)!important}.h-1\.5{height:calc(var(--spacing)*1.5)}.h-2\.5{height:calc(var(--spacing)*2.5)}.h-3\.5{height:calc(var(--spacing)*3.5)}.h-7{height:calc(var(--spacing)*7)}.h-8{height:calc(var(--spacing)*8)}.h-9{height:calc(var(--spacing)*9)}.h-10{height:calc(var(--spacing)*10)}.h-14\.5{height:calc(var(--spacing)*14.5)}.h-dvh{height:100dvh}.h-full{height:100%}.min-h-screen{min-height:100vh}.min-h-svh{min-height:100svh}.w-1\.5{width:calc(var(--spacing)*1.5)}.w-2\.5{width:calc(var(--spacing)*2.5)}.w-3\.5{width:calc(var(--spacing)*3.5)}.w-8{width:calc(var(--spacing)*8)}.w-9{width:calc(var(--spacing)*9)}.w-10{width:calc(var(--spacing)*10)}.w-\[220px\]{width:220px}.w-\[448px\]{width:448px}.w-full{width:100%}.w-px{width:1px}.max-w-\[335px\]{max-width:335px}.max-w-lg{max-width:var(--container-lg)}.max-w-md{max-width:var(--container-md)}.max-w-none{max-width:none}.max-w-sm{max-width:var(--container-sm)}.flex-1{flex:1}.shrink-0{flex-shrink:0}.translate-y-0{--tw-translate-y:calc(var(--spacing)*0);translate:var(--tw-translate-x)var(--tw-translate-y)}.cursor-pointer{cursor:pointer}.auto-rows-min{grid-auto-rows:min-content}.flex-col{flex-direction:column}.flex-col-reverse{flex-direction:column-reverse}.items-center{align-items:center}.items-start{align-items:flex-start}.justify-between{justify-content:space-between}.justify-center{justify-content:center}.justify-end{justify-content:flex-end}.gap-2{gap:calc(var(--spacing)*2)}.gap-3{gap:calc(var(--spacing)*3)}.gap-4{gap:calc(var(--spacing)*4)}.gap-6{gap:calc(var(--spacing)*6)}:where(.space-y-2>:not(:last-child)){--tw-space-y-reverse:0;margin-block-start:calc(calc(var(--spacing)*2)*var(--tw-space-y-reverse));margin-block-end:calc(calc(var(--spacing)*2)*calc(1 - var(--tw-space-y-reverse)))}:where(.space-y-3>:not(:last-child)){--tw-space-y-reverse:0;margin-block-start:calc(calc(var(--spacing)*3)*var(--tw-space-y-reverse));margin-block-end:calc(calc(var(--spacing)*3)*calc(1 - var(--tw-space-y-reverse)))}:where(.space-y-6>:not(:last-child)){--tw-space-y-reverse:0;margin-block-start:calc(calc(var(--spacing)*6)*var(--tw-space-y-reverse));margin-block-end:calc(calc(var(--spacing)*6)*calc(1 - var(--tw-space-y-reverse)))}:where(.space-y-\[2px\]>:not(:last-child)){--tw-space-y-reverse:0;margin-block-start:calc(2px*var(--tw-space-y-reverse));margin-block-end:calc(2px*calc(1 - var(--tw-space-y-reverse)))}:where(.space-x-0\.5>:not(:last-child)){--tw-space-x-reverse:0;margin-inline-start:calc(calc(var(--spacing)*.5)*var(--tw-space-x-reverse));margin-inline-end:calc(calc(var(--spacing)*.5)*calc(1 - var(--tw-space-x-reverse)))}:where(.space-x-1>:not(:last-child)){--tw-space-x-reverse:0;margin-inline-start:calc(calc(var(--spacing)*1)*var(--tw-space-x-reverse));margin-inline-end:calc(calc(var(--spacing)*1)*calc(1 - var(--tw-space-x-reverse)))}:where(.space-x-2>:not(:last-child)){--tw-space-x-reverse:0;margin-inline-start:calc(calc(var(--spacing)*2)*var(--tw-space-x-reverse));margin-inline-end:calc(calc(var(--spacing)*2)*calc(1 - var(--tw-space-x-reverse)))}.self-stretch{align-self:stretch}.truncate{text-overflow:ellipsis;white-space:nowrap;overflow:hidden}.overflow-hidden{overflow:hidden}.rounded-full{border-radius:3.40282e38px}.rounded-lg{border-radius:var(--radius-lg)}.rounded-md{border-radius:var(--radius-md)}.rounded-sm{border-radius:var(--radius-sm)}.rounded-xl{border-radius:var(--radius-xl)}.rounded-ee-lg{border-end-end-radius:var(--radius-lg)}.rounded-es-lg{border-end-start-radius:var(--radius-lg)}.rounded-t-lg{border-top-left-radius:var(--radius-lg);border-top-right-radius:var(--radius-lg)}.border{border-style:var(--tw-border-style);border-width:1px}.border-r{border-right-style:var(--tw-border-style);border-right-width:1px}.border-b{border-bottom-style:var(--tw-border-style);border-bottom-width:1px}.border-\[\#19140035\]{border-color:#19140035}.border-\[\#e3e3e0\]{border-color:#e3e3e0}.border-black{border-color:var(--color-black)}.border-neutral-200{border-color:var(--color-neutral-200)}.border-transparent{border-color:#0000}.border-zinc-200{border-color:var(--color-zinc-200)}.bg-\[\#1b1b18\]{background-color:#1b1b18}.bg-\[\#FDFDFC\]{background-color:#fdfdfc}.bg-\[\#dbdbd7\]{background-color:#dbdbd7}.bg-\[\#fff2f2\]{background-color:#fff2f2}.bg-neutral-100{background-color:var(--color-neutral-100)}.bg-neutral-200{background-color:var(--color-neutral-200)}.bg-neutral-900{background-color:var(--color-neutral-900)}.bg-white{background-color:var(--color-white)}.bg-zinc-50{background-color:var(--color-zinc-50)}.bg-zinc-200{background-color:var(--color-zinc-200)}.fill-current{fill:currentColor}.stroke-gray-900\/20{stroke:color-mix(in oklab,var(--color-gray-900)20%,transparent)}.p-0{padding:calc(var(--spacing)*0)}.p-6{padding:calc(var(--spacing)*6)}.p-10{padding:calc(var(--spacing)*10)}.px-1{padding-inline:calc(var(--spacing)*1)}.px-5{padding-inline:calc(var(--spacing)*5)}.px-8{padding-inline:calc(var(--spacing)*8)}.px-10{padding-inline:calc(var(--spacing)*10)}.py-0\!{padding-block:calc(var(--spacing)*0)!important}.py-1{padding-block:calc(var(--spacing)*1)}.py-1\.5{padding-block:calc(var(--spacing)*1.5)}.py-2{padding-block:calc(var(--spacing)*2)}.py-8{padding-block:calc(var(--spacing)*8)}.ps-3{padding-inline-start:calc(var(--spacing)*3)}.ps-7{padding-inline-start:calc(var(--spacing)*7)}.pe-4{padding-inline-end:calc(var(--spacing)*4)}.pb-4{padding-bottom:calc(var(--spacing)*4)}.pb-12{padding-bottom:calc(var(--spacing)*12)}.text-center{text-align:center}.text-start{text-align:start}.text-lg{font-size:var(--text-lg);line-height:var(--tw-leading,var(--text-lg--line-height))}.text-sm{font-size:var(--text-sm);line-height:var(--tw-leading,var(--text-sm--line-height))}.text-xs{font-size:var(--text-xs);line-height:var(--tw-leading,var(--text-xs--line-height))}.text-\[13px\]{font-size:13px}.leading-\[20px\]{--tw-leading:20px;line-height:20px}.leading-none{--tw-leading:1;line-height:1}.leading-normal{--tw-leading:var(--leading-normal);line-height:var(--leading-normal)}.leading-tight{--tw-leading:var(--leading-tight);line-height:var(--leading-tight)}.font-medium{--tw-font-weight:var(--font-weight-medium);font-weight:var(--font-weight-medium)}.font-normal{--tw-font-weight:var(--font-weight-normal);font-weight:var(--font-weight-normal)}.font-semibold{--tw-font-weight:var(--font-weight-semibold);font-weight:var(--font-weight-semibold)}.\!text-green-600{color:var(--color-green-600)!important}.text-\[\#1b1b18\]{color:#1b1b18}.text-\[\#706f6c\]{color:#706f6c}.text-\[\#F53003\],.text-\[\#f53003\]{color:#f53003}.text-black{color:var(--color-black)}.text-green-600{color:var(--color-green-600)}.text-stone-800{color:var(--color-stone-800)}.text-white{color:var(--color-white)}.text-zinc-400{color:var(--color-zinc-400)}.text-zinc-500{color:var(--color-zinc-500)}.text-zinc-600{color:var(--color-zinc-600)}.lowercase{text-transform:lowercase}.underline{text-decoration-line:underline}.underline-offset-4{text-underline-offset:4px}.antialiased{-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}.opacity-100{opacity:1}.shadow-\[0px_0px_1px_0px_rgba\(0\,0\,0\,0\.03\)\,0px_1px_2px_0px_rgba\(0\,0\,0\,0\.06\)\]{--tw-shadow:0px 0px 1px 0px var(--tw-shadow-color,#00000008),0px 1px 2px 0px var(--tw-shadow-color,#0000000f);box-shadow:var(--tw-inset-shadow),var(--tw-inset-ring-shadow),var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow)}.shadow-\[inset_0px_0px_0px_1px_rgba\(26\,26\,0\,0\.16\)\]{--tw-shadow:inset 0px 0px 0px 1px var(--tw-shadow-color,#1a1a0029);box-shadow:var(--tw-inset-shadow),var(--tw-inset-ring-shadow),var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow)}.shadow-xs{--tw-shadow:0 1px 2px 0 var(--tw-shadow-color,#0000000d);box-shadow:var(--tw-inset-shadow),var(--tw-inset-ring-shadow),var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow)}.outline{outline-style:var(--tw-outline-style);outline-width:1px}.transition-all{transition-property:all;transition-timing-function:var(--tw-ease,var(--default-transition-timing-function));transition-duration:var(--tw-duration,var(--default-transition-duration))}.transition-opacity{transition-property:opacity;transition-timing-function:var(--tw-ease,var(--default-transition-timing-function));transition-duration:var(--tw-duration,var(--default-transition-duration))}.delay-300{transition-delay:.3s}.duration-750{--tw-duration:.75s;transition-duration:.75s}.not-has-\[nav\]\:hidden:not(:has(:is(nav))){display:none}.group-data-open\/disclosure-button\:block:is(:where(.group\/disclosure-button)[data-open] *){display:block}.group-data-open\/disclosure-button\:hidden:is(:where(.group\/disclosure-button)[data-open] *){display:none}.before\:absolute:before{content:var(--tw-content);position:absolute}.before\:start-\[0\.4rem\]:before{content:var(--tw-content);inset-inline-start:.4rem}.before\:top-0:before{content:var(--tw-content);top:calc(var(--spacing)*0)}.before\:top-1\/2:before{content:var(--tw-content);top:50%}.before\:bottom-0:before{content:var(--tw-content);bottom:calc(var(--spacing)*0)}.before\:bottom-1\/2:before{content:var(--tw-content);bottom:50%}.before\:left-\[0\.4rem\]:before{content:var(--tw-content);left:.4rem}.before\:border-l:before{content:var(--tw-content);border-left-style:var(--tw-border-style);border-left-width:1px}.before\:border-\[\#e3e3e0\]:before{content:var(--tw-content);border-color:#e3e3e0}@media (hover:hover){.hover\:border-\[\#1915014a\]:hover{border-color:#1915014a}.hover\:border-\[\#19140035\]:hover{border-color:#19140035}.hover\:border-black:hover{border-color:var(--color-black)}.hover\:bg-black:hover{background-color:var(--color-black)}.hover\:bg-zinc-800\/5:hover{background-color:color-mix(in oklab,var(--color-zinc-800)5%,transparent)}.hover\:text-zinc-800:hover{color:var(--color-zinc-800)}}.data-open\:block[data-open]{display:block}@media (width<64rem){.max-lg\:hidden{display:none}}@media (width<48rem){.max-md\:flex-col{flex-direction:column}.max-md\:pt-6{padding-top:calc(var(--spacing)*6)}}@media (width>=40rem){.sm\:w-\[350px\]{width:350px}.sm\:px-0{padding-inline:calc(var(--spacing)*0)}}@media (width>=48rem){.md\:hidden{display:none}.md\:w-\[220px\]{width:220px}.md\:grid-cols-3{grid-template-columns:repeat(3,minmax(0,1fr))}.md\:p-10{padding:calc(var(--spacing)*10)}}@media (width>=64rem){.lg\:-ms-px{margin-inline-start:-1px}.lg\:ms-0{margin-inline-start:calc(var(--spacing)*0)}.lg\:-mt-\[6\.6rem\]{margin-top:-6.6rem}.lg\:mb-0{margin-bottom:calc(var(--spacing)*0)}.lg\:mb-6{margin-bottom:calc(var(--spacing)*6)}.lg\:block{display:block}.lg\:flex{display:flex}.lg\:hidden{display:none}.lg\:aspect-auto{aspect-ratio:auto}.lg\:h-8{height:calc(var(--spacing)*8)}.lg\:w-\[438px\]{width:438px}.lg\:max-w-4xl{max-width:var(--container-4xl)}.lg\:max-w-none{max-width:none}.lg\:grow{flex-grow:1}.lg\:grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}.lg\:flex-row{flex-direction:row}.lg\:justify-center{justify-content:center}.lg\:rounded-ss-lg{border-start-start-radius:var(--radius-lg)}.lg\:rounded-e-lg{border-start-end-radius:var(--radius-lg);border-end-end-radius:var(--radius-lg)}.lg\:rounded-e-lg\!{border-start-end-radius:var(--radius-lg)!important;border-end-end-radius:var(--radius-lg)!important}.lg\:rounded-ee-none{border-end-end-radius:0}.lg\:rounded-t-none{border-top-left-radius:0;border-top-right-radius:0}.lg\:p-8{padding:calc(var(--spacing)*8)}.lg\:p-20{padding:calc(var(--spacing)*20)}.lg\:px-0{padding-inline:calc(var(--spacing)*0)}}:where(.rtl\:space-x-reverse:where(:dir(rtl),[dir=rtl],[dir=rtl] *)>:not(:last-child)){--tw-space-x-reverse:1}@media (prefers-color-scheme:dark){.dark\:block{display:block}.dark\:hidden{display:none}.dark\:border-r{border-right-style:var(--tw-border-style);border-right-width:1px}.dark\:border-\[\#3E3E3A\]{border-color:#3e3e3a}.dark\:border-\[\#eeeeec\]{border-color:#eeeeec}.dark\:border-neutral-700{border-color:var(--color-neutral-700)}.dark\:border-neutral-800{border-color:var(--color-neutral-800)}.dark\:border-stone-800{border-color:var(--color-stone-800)}.dark\:border-zinc-700{border-color:var(--color-zinc-700)}.dark\:bg-\[\#0a0a0a\]{background-color:#0a0a0a}.dark\:bg-\[\#1D0002\]{background-color:#1d0002}.dark\:bg-\[\#3E3E3A\]{background-color:#3e3e3a}.dark\:bg-\[\#161615\]{background-color:#161615}.dark\:bg-\[\#eeeeec\]{background-color:#eeeeec}.dark\:bg-neutral-700{background-color:var(--color-neutral-700)}.dark\:bg-stone-950{background-color:var(--color-stone-950)}.dark\:bg-white\/30{background-color:color-mix(in oklab,var(--color-white)30%,transparent)}.dark\:bg-zinc-800{background-color:var(--color-zinc-800)}.dark\:bg-zinc-900{background-color:var(--color-zinc-900)}.dark\:bg-linear-to-b{--tw-gradient-position:to bottom in oklab;background-image:linear-gradient(var(--tw-gradient-stops))}.dark\:from-neutral-950{--tw-gradient-from:var(--color-neutral-950);--tw-gradient-stops:var(--tw-gradient-via-stops,var(--tw-gradient-position),var(--tw-gradient-from)var(--tw-gradient-from-position),var(--tw-gradient-to)var(--tw-gradient-to-position))}.dark\:to-neutral-900{--tw-gradient-to:var(--color-neutral-900);--tw-gradient-stops:var(--tw-gradient-via-stops,var(--tw-gradient-position),var(--tw-gradient-from)var(--tw-gradient-from-position),var(--tw-gradient-to)var(--tw-gradient-to-position))}.dark\:stroke-neutral-100\/20{stroke:color-mix(in oklab,var(--color-neutral-100)20%,transparent)}.dark\:text-\[\#1C1C1A\]{color:#1c1c1a}.dark\:text-\[\#A1A09A\]{color:#a1a09a}.dark\:text-\[\#EDEDEC\]{color:#ededec}.dark\:text-\[\#F61500\]{color:#f61500}.dark\:text-\[\#FF4433\]{color:#f43}.dark\:text-black{color:var(--color-black)}.dark\:text-white{color:var(--color-white)}.dark\:text-white\/80{color:color-mix(in oklab,var(--color-white)80%,transparent)}.dark\:text-zinc-400{color:var(--color-zinc-400)}.dark\:shadow-\[inset_0px_0px_0px_1px_\#fffaed2d\]{--tw-shadow:inset 0px 0px 0px 1px var(--tw-shadow-color,#fffaed2d);box-shadow:var(--tw-inset-shadow),var(--tw-inset-ring-shadow),var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow)}.dark\:before\:border-\[\#3E3E3A\]:before{content:var(--tw-content);border-color:#3e3e3a}@media (hover:hover){.dark\:hover\:border-\[\#3E3E3A\]:hover{border-color:#3e3e3a}.dark\:hover\:border-\[\#62605b\]:hover{border-color:#62605b}.dark\:hover\:border-white:hover{border-color:var(--color-white)}.dark\:hover\:bg-white:hover{background-color:var(--color-white)}.dark\:hover\:bg-white\/\[7\%\]:hover{background-color:color-mix(in oklab,var(--color-white)7%,transparent)}.dark\:hover\:text-white:hover{color:var(--color-white)}}}@starting-style{.starting\:translate-y-4{--tw-translate-y:calc(var(--spacing)*4);translate:var(--tw-translate-x)var(--tw-translate-y)}}@starting-style{.starting\:translate-y-6{--tw-translate-y:calc(var(--spacing)*6);translate:var(--tw-translate-x)var(--tw-translate-y)}}@starting-style{.starting\:opacity-0{opacity:0}}.\[\&\>div\>svg\]\:size-5>div>svg{width:calc(var(--spacing)*5);height:calc(var(--spacing)*5)}:where(.\[\:where\(\&\)\]\:size-4){width:calc(var(--spacing)*4);height:calc(var(--spacing)*4)}:where(.\[\:where\(\&\)\]\:size-5){width:calc(var(--spacing)*5);height:calc(var(--spacing)*5)}:where(.\[\:where\(\&\)\]\:size-6){width:calc(var(--spacing)*6);height:calc(var(--spacing)*6)}}@property --tw-translate-x{syntax:"*";inherits:false;initial-value:0}@property --tw-translate-y{syntax:"*";inherits:false;initial-value:0}@property --tw-translate-z{syntax:"*";inherits:false;initial-value:0}@property --tw-space-y-reverse{syntax:"*";inherits:false;initial-value:0}@property --tw-space-x-reverse{syntax:"*";inherits:false;initial-value:0}@property --tw-border-style{syntax:"*";inherits:false;initial-value:solid}@property --tw-leading{syntax:"*";inherits:false}@property --tw-font-weight{syntax:"*";inherits:false}@property --tw-shadow{syntax:"*";inherits:false;initial-value:0 0 #0000}@property --tw-shadow-color{syntax:"*";inherits:false}@property --tw-inset-shadow{syntax:"*";inherits:false;initial-value:0 0 #0000}@property --tw-inset-shadow-color{syntax:"*";inherits:false}@property --tw-ring-color{syntax:"*";inherits:false}@property --tw-ring-shadow{syntax:"*";inherits:false;initial-value:0 0 #0000}@property --tw-inset-ring-color{syntax:"*";inherits:false}@property --tw-inset-ring-shadow{syntax:"*";inherits:false;initial-value:0 0 #0000}@property --tw-ring-inset{syntax:"*";inherits:false}@property --tw-ring-offset-width{syntax:"<length>";inherits:false;initial-value:0}@property --tw-ring-offset-color{syntax:"*";inherits:false;initial-value:#fff}@property --tw-ring-offset-shadow{syntax:"*";inherits:false;initial-value:0 0 #0000}@property --tw-outline-style{syntax:"*";inherits:false;initial-value:solid}@property --tw-duration{syntax:"*";inherits:false}@property --tw-content{syntax:"*";inherits:false;initial-value:""}@property --tw-gradient-position{syntax:"*";inherits:false}@property --tw-gradient-from{syntax:"<color>";inherits:false;initial-value:#0000}@property --tw-gradient-via{syntax:"<color>";inherits:false;initial-value:#0000}@property --tw-gradient-to{syntax:"<color>";inherits:false;initial-value:#0000}@property --tw-gradient-stops{syntax:"*";inherits:false}@property --tw-gradient-via-stops{syntax:"*";inherits:false}@property --tw-gradient-from-position{syntax:"<length-percentage>";inherits:false;initial-value:0%}@property --tw-gradient-via-position{syntax:"<length-percentage>";inherits:false;initial-value:50%}            @property --tw-gradient-to-position{syntax:"<length-percentage>";inherits:false;initial-value:100%}
            
            /* Circular menu styles */
            .circular-menu-container {
                margin: 0 auto;
                transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
                position: relative;
            }
            /* Точное центрирование меню */
            main {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                flex: 1;
            }
            /* Убеждаемся, что контейнер центрирован */
            #circular-menu-container {
                position: relative;
                width: 540px;
                height: 540px;
                margin: 0 auto;
                overflow: visible;
            }
            /* Внешнее кольцо для дочерних элементов */
            #children-ring-svg {
                transition: opacity 0.4s ease-in-out;
                overflow: visible;
                z-index: 1;
            }
            #children-ring-svg.children-menu-hidden {
                display: none !important;
                opacity: 0 !important;
                visibility: hidden !important;
            }
            #children-ring-svg.show {
                display: block !important;
                pointer-events: all !important;
                opacity: 1 !important;
                visibility: visible !important;
            }
            /* Основное кольцо должно быть под внешним при отображении дочерних элементов */
            #ring-svg {
                position: relative;
                z-index: 1;
            }
            .children-sector-path {
                fill: white;
                stroke: #e3e3e0;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            .dark .children-sector-path {
                fill: #161615;
                stroke: #3E3E3A;
            }
            .children-sector-path:hover {
                opacity: 0.9;
                filter: brightness(0.95);
            }
            .dark .children-sector-path:hover {
                filter: brightness(1.1);
            }
            .children-sector-text {
                fill: #1b1b18;
                pointer-events: none;
            }
            .dark .children-sector-text {
                fill: #EDEDEC;
            }
            @keyframes slowRotate {
                from {
                    transform: rotate(0deg);
                }
                to {
                    transform: rotate(360deg);
                }
            }
            #ring-svg {
                animation: slowRotate 180s linear infinite;
                transform-origin: 270px 270px;
                display: block;
            }
            /* SVG индикаторов синхронизируется с основным SVG */
            #indicators-svg {
                animation: slowRotate 180s linear infinite;
                transform-origin: 270px 270px;
            }
            /* Логотип не вращается - компенсируем вращение родителя */
            .logo-center {
                animation: counterRotate 180s linear infinite;
                transform-origin: center center;
            }
            @keyframes counterRotate {
                from {
                    transform: rotate(0deg);
                }
                to {
                    transform: rotate(-360deg);
                }
            }
            #ring-svg.rotating {
                animation-play-state: paused;
            }
            #ring-svg {
                transition: transform 0.8s cubic-bezier(0.4, 0, 0.2, 1), width 0.6s cubic-bezier(0.4, 0, 0.2, 1), height 0.6s cubic-bezier(0.4, 0, 0.2, 1) !important;
            }
            .circular-menu-container.moving {
                position: fixed !important;
                top: 24px !important;
                left: 24px !important;
                width: 200px !important;
                height: 200px !important;
                z-index: 1000 !important;
            }
            .circular-menu-container.moving #ring-svg {
                width: 200px !important;
                height: 200px !important;
            }
            .sector-path {
                fill: white;
                stroke: #e3e3e0;
            }
            .dark .sector-path {
                fill: #161615;
                stroke: #3E3E3A;
            }
            .sector-path:hover {
                opacity: 0.9;
                filter: brightness(0.95);
            }
            .dark .sector-path:hover {
                filter: brightness(1.1);
            }
            .sector-path.has-children {
                stroke-width: 3;
            }
            .sector-path.has-children:hover {
                filter: brightness(1.1);
            }
            .dark .sector-path.has-children:hover {
                filter: brightness(1.2);
            }
            .children-indicator {
                display: block;
                pointer-events: all;
                cursor: pointer;
            }
            #indicators-group {
                pointer-events: all;
            }
            @php
                use App\Helpers\LogoHelper;
                $logoClass = LogoHelper::getClass('center');
                $lightFilter = config('logo.filters.light', 'brightness(0)');
                $darkFilter = config('logo.filters.dark', 'brightness(0) invert(1)');
                $themeSelectors = config('logo.theme_selectors', []);
                $mediaQuery = config('logo.media_query', '(prefers-color-scheme: dark)');
            @endphp
            .{{ $logoClass }} {
                filter: {{ $lightFilter }};
            }
            @foreach($themeSelectors as $selector)
            {{ $selector }} .{{ $logoClass }} {
                filter: {{ $darkFilter }};
            }
            @endforeach
            @media {{ $mediaQuery }} {
                .{{ $logoClass }}:not(.light-theme) {
                    filter: {{ $darkFilter }};
                }
            }
            .sector-text {
                fill: #1b1b18;
            }
            .dark .sector-text {
                fill: #EDEDEC;
            }
        </style>
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex flex-col min-h-screen p-6 lg:p-8">
        <header class="w-full lg:max-w-4xl max-w-[335px] mx-auto text-sm mb-6 not-has-[nav]:hidden">
            @if (Route::has('login'))
                <nav class="flex items-center justify-center gap-4">
                    @auth
                        <a
                            href="{{ url('/dashboard') }}"
                            class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] text-[#1b1b18] border border-transparent hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
                        >
                            {{ __('ui.dashboard') }}
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] text-[#1b1b18] border border-transparent hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
                        >
                            {{ __('ui.auth.login.button') }}
                        </a>

                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] text-[#1b1b18] border border-transparent hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal">
                                {{ __('ui.auth.register.home_button') }}
                            </a>
                        @endif
                    @endauth
                </nav>
            @endif
        </header>
        <main class="flex-1 flex items-center justify-center w-full transition-opacity opacity-100 duration-750 starting:opacity-0">
            @if(isset($menuItems) && count($menuItems) > 0)
                <div id="circular-menu-container" class="circular-menu-container relative">
                    @php
                        // Общие переменные для основного и внешнего колец
                        $innerRadius = 200;
                        $outerRadius = 250;
                        $centerX = 270; // Центр основного SVG (540/2)
                        $centerY = 270;
                        $totalItems = count($menuItems);
                        $angleStep = 360 / $totalItems;
                        $isAuthenticated = auth()->check();
                    @endphp
                    <!-- Внешнее кольцо для дочерних элементов (скрыто по умолчанию) -->
                    <svg id="children-ring-svg" width="800" height="800" viewBox="0 0 800 800" class="absolute children-menu-hidden" style="top: -130px; left: -130px; transform-origin: 400px 400px; pointer-events: none; z-index: 2;">
                        <g id="children-sectors-group" style="pointer-events: all;"></g>
                    </svg>
                    <!-- Основное кольцо меню -->
                    <svg id="ring-svg" width="540" height="540" viewBox="0 0 540 540" style="transform-origin: 270px 270px; transition: transform 0.8s cubic-bezier(0.4, 0, 0.2, 1), width 0.6s cubic-bezier(0.4, 0, 0.2, 1), height 0.6s cubic-bezier(0.4, 0, 0.2, 1); position: relative; z-index: 1;">
                            @php
                                // Радиус лого = радиус внутреннего кольца - 5px
                                $logoRadius = $innerRadius - 5; // 200 - 5 = 195
                                $logoSize = $logoRadius * 2; // 195 * 2 = 390
                                $logoX = $centerX - ($logoSize / 2);
                                $logoY = $centerY - ($logoSize / 2);
                            @endphp
                            <!-- Логотип в центре круга -->
                            @php
                                $logoPath = LogoHelper::getPath();
                                $logoCenterClass = LogoHelper::getClass('center');
                            @endphp
                            <image 
                                href="{{ $logoPath }}"
                                x="{{ $logoX }}"
                                y="{{ $logoY }}"
                                width="{{ $logoSize }}"
                                height="{{ $logoSize }}"
                                class="{{ $logoCenterClass }}"
                                style="pointer-events: none;"
                            />
                            @foreach($menuItems as $index => $item)
                                @php
                                    $startAngle = ($index * $angleStep) - 90;
                                    $endAngle = (($index + 1) * $angleStep) - 90;
                                    $startAngleRad = deg2rad($startAngle);
                                    $endAngleRad = deg2rad($endAngle);
                                    
                                    // Точки для внутреннего радиуса
                                    $innerStartX = $centerX + $innerRadius * cos($startAngleRad);
                                    $innerStartY = $centerY + $innerRadius * sin($startAngleRad);
                                    $innerEndX = $centerX + $innerRadius * cos($endAngleRad);
                                    $innerEndY = $centerY + $innerRadius * sin($endAngleRad);
                                    
                                    // Точки для внешнего радиуса
                                    $outerStartX = $centerX + $outerRadius * cos($startAngleRad);
                                    $outerStartY = $centerY + $outerRadius * sin($startAngleRad);
                                    $outerEndX = $centerX + $outerRadius * cos($endAngleRad);
                                    $outerEndY = $centerY + $outerRadius * sin($endAngleRad);
                                    
                                    // Флаг для большого дуги (если угол больше 180 градусов)
                                    $largeArcFlag = ($angleStep > 180) ? 1 : 0;
                                    
                                    // Путь для сектора
                                    $path = "M {$innerStartX} {$innerStartY} ";
                                    $path .= "L {$outerStartX} {$outerStartY} ";
                                    $path .= "A {$outerRadius} {$outerRadius} 0 {$largeArcFlag} 1 {$outerEndX} {$outerEndY} ";
                                    $path .= "L {$innerEndX} {$innerEndY} ";
                                    $path .= "A {$innerRadius} {$innerRadius} 0 {$largeArcFlag} 0 {$innerStartX} {$innerStartY} ";
                                    $path .= "Z";
                                    
                                    // Угол для текста (середина сектора)
                                    $textAngle = ($startAngle + $endAngle) / 2;
                                    $textAngleRad = deg2rad($textAngle);
                                    $textRadius = ($innerRadius + $outerRadius) / 2;
                                    $textX = $centerX + $textRadius * cos($textAngleRad);
                                    $textY = $centerY + $textRadius * sin($textAngleRad);
                                    
                                    // Создаем путь-дугу для текста
                                    $textPathStartX = $centerX + $textRadius * cos($startAngleRad);
                                    $textPathStartY = $centerY + $textRadius * sin($startAngleRad);
                                    $textPathEndX = $centerX + $textRadius * cos($endAngleRad);
                                    $textPathEndY = $centerY + $textRadius * sin($endAngleRad);
                                    $textPathLargeArcFlag = ($angleStep > 180) ? 1 : 0;
                                    $textPathId = "text-path-{$index}";
                                    $textPath = "M {$textPathStartX} {$textPathStartY} A {$textRadius} {$textRadius} 0 {$textPathLargeArcFlag} 1 {$textPathEndX} {$textPathEndY}";
                                    
                                    // Координаты для индикатора подуровня (на границе внешнего круга)
                                    $indicatorRadius = $outerRadius; // Центр индикатора на границе кольца
                                    $indicatorX = $centerX + $indicatorRadius * cos($textAngleRad);
                                    $indicatorY = $centerY + $indicatorRadius * sin($textAngleRad);
                                    
                                    // Получаем URL и название из сервиса
                                    $itemUrl = \App\Services\MenuService::buildUrl($item, $isAuthenticated);
                                    $itemName = \App\Services\MenuService::getTranslatedName($item);
                                    $hasChildren = isset($item['children']) && is_array($item['children']) && count($item['children']) > 0;
                                    
                                    // Подготавливаем дочерние элементы с готовыми URL и переведенными названиями
                                    $childrenData = [];
                                    if ($hasChildren) {
                                        foreach ($item['children'] as $child) {
                                            $childUrl = \App\Services\MenuService::buildUrl($child, $isAuthenticated);
                                            $childName = \App\Services\MenuService::getTranslatedName($child);
                                            $childrenData[] = [
                                                'id' => $child['id'] ?? null,
                                                'name' => $child['name'] ?? '',
                                                'translated_name' => $childName,
                                                'url' => $childUrl,
                                                'href' => $child['href'] ?? '#',
                                                'href_params' => $child['href_params'] ?? [],
                                                'guest_href' => $child['guest_href'] ?? null,
                                                'guest_href_params' => $child['guest_href_params'] ?? [],
                                            ];
                                        }
                                    }
                                @endphp
                                <g class="sector-group" data-index="{{ $index }}" data-angle="{{ $startAngle }}" data-center-angle="{{ ($startAngle + $endAngle) / 2 }}" data-has-children="{{ $hasChildren ? 'true' : 'false' }}" data-item-id="{{ $item['id'] ?? $index }}">
                                        <defs>
                                            <path id="{{ $textPathId }}" d="{{ $textPath }}" />
                                        </defs>
                                        <path
                                        d="{{ $path }}"
                                        class="sector-path cursor-pointer transition-all duration-300 {{ $hasChildren ? 'has-children' : '' }}"
                                        stroke-width="2"
                                        data-href="{{ $itemUrl }}"
                                        data-item-id="{{ $item['id'] ?? $index }}"
                                        style="pointer-events: all;"
                                    />
                                    <text 
                                        class="sector-text pointer-events-none text-xs font-medium"
                                    >
                                        <textPath href="#{{ $textPathId }}" startOffset="50%" text-anchor="middle" dominant-baseline="middle">
                                            {{ $itemName }}
                                        </textPath>
                                    </text>
                                </g>
                            @endforeach
                                    </svg>
                    <!-- Отдельный SVG для индикаторов - синхронизируется с трансформациями основного SVG -->
                    <svg id="indicators-svg" width="540" height="540" viewBox="0 0 540 540" class="absolute" style="top: 0; left: 0; pointer-events: none; z-index: 100; transform-origin: 270px 270px;">
                        <g id="indicators-group" style="pointer-events: all;">
                            @foreach($menuItems as $index => $item)
                                @php
                                    $hasChildren = isset($item['children']) && is_array($item['children']) && count($item['children']) > 0;
                                    if (!$hasChildren) continue;
                                    
                                    $startAngle = ($index * $angleStep) - 90;
                                    $endAngle = (($index + 1) * $angleStep) - 90;
                                    $textAngle = ($startAngle + $endAngle) / 2;
                                    $textAngleRad = deg2rad($textAngle);
                                    
                                    // Координаты для индикатора подуровня (на границе внешнего круга)
                                    $indicatorRadius = $outerRadius;
                                    $indicatorX = $centerX + $indicatorRadius * cos($textAngleRad);
                                    $indicatorY = $centerY + $indicatorRadius * sin($textAngleRad);
                                    
                                    // Подготавливаем дочерние элементы с готовыми URL и переведенными названиями
                                    $childrenData = [];
                                    foreach ($item['children'] as $child) {
                                        $childUrl = \App\Services\MenuService::buildUrl($child, $isAuthenticated);
                                        $childName = \App\Services\MenuService::getTranslatedName($child);
                                        $childrenData[] = [
                                            'id' => $child['id'] ?? null,
                                            'name' => $child['name'] ?? '',
                                            'translated_name' => $childName,
                                            'url' => $childUrl,
                                            'href' => $child['href'] ?? '#',
                                            'href_params' => $child['href_params'] ?? [],
                                            'guest_href' => $child['guest_href'] ?? null,
                                            'guest_href_params' => $child['guest_href_params'] ?? [],
                                        ];
                                    }
                                @endphp
                                <circle 
                                    cx="{{ $indicatorX }}" 
                                    cy="{{ $indicatorY }}" 
                                    r="10" 
                                    class="children-indicator fill-current text-[#1b1b18] dark:text-[#EDEDEC] cursor-pointer"
                                    style="pointer-events: all; cursor: pointer;"
                                    data-children='@json($childrenData)'
                                    data-parent-id="{{ $item['id'] ?? $index }}"
                                />
                            @endforeach
                        </g>
                    </svg>
                </div>
            @else
                <div class="text-center">
                    <p class="text-[#706f6c] dark:text-[#A1A09A]">Нет доступных типов ресурсов</p>
                </div>
            @endif
            </main>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const container = document.getElementById('circular-menu-container');
                const svg = document.getElementById('ring-svg');
                const sectors = document.querySelectorAll('.sector-path');
                
                if (!container || !svg || sectors.length === 0) return;
                
                // Обновление цветов при изменении темы
                function updateColors() {
                    // Проверяем и класс dark, и системную тему
                    const hasDarkClass = document.documentElement.classList.contains('dark');
                    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                    const hasLightClass = document.documentElement.classList.contains('light');
                    // Используем класс dark, если он есть, иначе системную тему (если нет принудительной светлой темы)
                    const isDark = hasDarkClass || (prefersDark && !hasLightClass);
                    
                    sectors.forEach(path => {
                        if (isDark) {
                            path.setAttribute('fill', '#161615');
                            path.setAttribute('stroke', '#3E3E3A');
                        } else {
                            path.setAttribute('fill', 'white');
                            path.setAttribute('stroke', '#e3e3e0');
                        }
                    });
                    
                    const texts = document.querySelectorAll('.sector-text');
                    texts.forEach(text => {
                        if (isDark) {
                            text.setAttribute('fill', '#EDEDEC');
                        } else {
                            text.setAttribute('fill', '#1b1b18');
                        }
                    });
                    
                    // Обновление цвета логотипа
                    @php
                        $lightFilterJs = config('logo.filters.light', 'brightness(0)');
                        $darkFilterJs = config('logo.filters.dark', 'brightness(0) invert(1)');
                        $logoClassJs = LogoHelper::getClass('center');
                    @endphp
                    const logo = document.querySelector('.{{ $logoClassJs }}');
                    if (logo) {
                        if (isDark) {
                            logo.style.filter = '{{ $darkFilterJs }}';
                        } else {
                            logo.style.filter = '{{ $lightFilterJs }}';
                        }
                    }
                    
                    // Обновление favicon
                    updateFavicon(isDark);
                }
                
                // Обновление favicon в зависимости от темы
                function updateFavicon(isDark) {
                    const faviconSvg = document.getElementById('favicon-svg');
                    const faviconIco = document.getElementById('favicon-ico');
                    
                    if (faviconSvg) {
                        // Обновляем SVG favicon
                        if (isDark) {
                            faviconSvg.href = '/favicon-dark.svg';
                        } else {
                            faviconSvg.href = '/favicon.svg';
                        }
                    }
                }
                
                // Наблюдатель за изменением темы
                const observer = new MutationObserver(updateColors);
                observer.observe(document.documentElement, {
                    attributes: true,
                    attributeFilter: ['class']
                });
                
                // Слушатель изменения системной темы
                if (window.matchMedia) {
                    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
                    // Используем addEventListener для современных браузеров или addListener для старых
                    if (mediaQuery.addEventListener) {
                        mediaQuery.addEventListener('change', updateColors);
                    } else if (mediaQuery.addListener) {
                        mediaQuery.addListener(updateColors);
                    }
                }
                
                // Инициализация цветов
                updateColors();
                
                let currentRotation = 0;
                let isAnimating = false;
                
                sectors.forEach(sector => {
                    sector.addEventListener('click', function(e) {
                        // Проверяем, не был ли клик на индикаторе
                        const target = e.target;
                        if (target && target.classList && target.classList.contains('children-indicator')) {
                            return; // Клик на индикатор обрабатывается отдельно
                        }
                        
                        if (isAnimating) return;
                        
                        e.preventDefault();
                        isAnimating = true;
                        
                        const sectorGroup = this.closest('.sector-group');
                        const centerAngle = parseFloat(sectorGroup.getAttribute('data-center-angle'));
                        const hasChildren = sectorGroup.getAttribute('data-has-children') === 'true';
                        const itemId = sectorGroup.getAttribute('data-item-id');
                        const href = this.getAttribute('data-href');
                        
                        // Если есть подуровни, не обрабатываем клик на путь (индикатор обработает отдельно)
                        if (hasChildren) {
                            isAnimating = false;
                            return;
                        }
                        
                        // Вычисляем угол поворота: нужно повернуть так, чтобы центр сектора был справа от вертикальной оси
                        // Вертикальная ось = 0° (правая сторона круга)
                        // Центр сектора должен быть на 0° или немного правее (например, 15°)
                        // Текущий угол центра сектора: centerAngle
                        // Нужный угол: 0° (или 15° для небольшого смещения вправо)
                        const targetPosition = 15; // Смещение вправо от вертикали в градусах
                        const targetRotation = targetPosition - centerAngle;
                        
                        // Останавливаем анимацию и поворачиваем к нужному углу
                        svg.style.animationPlayState = 'paused';
                        // Поворачиваем к нужному углу
                        svg.style.transform = `rotate(${targetRotation}deg)`;
                        currentRotation = targetRotation;
                        
                        // После завершения анимации поворота, возобновляем медленное вращение
                        setTimeout(() => {
                            svg.style.animationPlayState = 'running';
                            // Синхронизируем возобновление анимации с SVG индикаторов
                            const indicatorsSvg = document.getElementById('indicators-svg');
                            if (indicatorsSvg) {
                                indicatorsSvg.style.animationPlayState = 'running';
                            }
                        }, 800);
                        
                        // После поворота смещаем в левый верхний угол
                        setTimeout(() => {
                            // Добавляем класс для анимации перемещения и уменьшения
                            container.classList.add('moving');
                            
                            // Применяем масштабирование через requestAnimationFrame для плавности
                            requestAnimationFrame(() => {
                                svg.style.transform = `rotate(${targetRotation}deg) scale(0.4)`;
                                // Синхронизируем масштабирование с SVG индикаторов
                                const indicatorsSvg = document.getElementById('indicators-svg');
                                if (indicatorsSvg) {
                                    indicatorsSvg.style.transform = `rotate(${targetRotation}deg) scale(0.4)`;
                                }
                            });
                            
                            // Переходим на страницу после завершения анимации
                            setTimeout(() => {
                                window.location.href = href;
                            }, 600);
                        }, 800);
                    });
                });
                
                // Обработка клика на индикатор дочерних элементов (toggle)
                const indicators = document.querySelectorAll('.children-indicator');
                indicators.forEach(indicator => {
                    indicator.addEventListener('click', function(e) {
                        e.stopPropagation();
                        e.preventDefault();
                        
                        // Не обрабатываем, если идет анимация
                        if (isAnimating) return;
                        
                        const childrenDataStr = this.getAttribute('data-children');
                        if (!childrenDataStr) return;
                        
                        let childrenData;
                        try {
                            childrenData = JSON.parse(childrenDataStr);
                        } catch (err) {
                            console.error('Error parsing children data:', err);
                            return;
                        }
                        
                        if (!Array.isArray(childrenData) || childrenData.length === 0) return;
                        
                        const parentId = this.getAttribute('data-parent-id');
                        if (!parentId) return;
                        
                        const childrenSvg = document.getElementById('children-ring-svg');
                        if (!childrenSvg) return;
                        
                        // Проверяем, видно ли подменю для этого родителя
                        const existingParentId = childrenSvg.getAttribute('data-parent-id');
                        // Проверяем видимость более надежным способом
                        const computedStyle = window.getComputedStyle(childrenSvg);
                        const isVisible = childrenSvg.classList.contains('show') && 
                                         computedStyle.display !== 'none' &&
                                         computedStyle.visibility !== 'hidden' &&
                                         computedStyle.opacity !== '0' &&
                                         existingParentId === parentId;
                        
                        console.log('Toggle check:', {
                            hasShowClass: childrenSvg.classList.contains('show'),
                            display: computedStyle.display,
                            visibility: computedStyle.visibility,
                            opacity: computedStyle.opacity,
                            existingParentId: existingParentId,
                            currentParentId: parentId,
                            isVisible: isVisible
                        });
                        
                        if (isVisible) {
                            // Если видно - скрываем
                            console.log('Hiding children menu');
                            hideChildrenMenu();
                        } else {
                            // Если скрыто или для другого родителя - показываем/обновляем
                            console.log('Showing children menu');
                            showChildrenMenu(childrenData, parentId);
                        }
                    }, true); // Используем capture phase для приоритета обработки
                });
            });
            
            // Функция для отображения дочерних элементов на внешнем кольце
            function showChildrenMenu(children, parentId) {
                const mainSvg = document.getElementById('ring-svg');
                const childrenSvg = document.getElementById('children-ring-svg');
                const childrenGroup = document.getElementById('children-sectors-group');
                
                if (!childrenSvg || !childrenGroup) {
                    console.error('Children menu elements not found:', {childrenSvg, childrenGroup});
                    return;
                }
                
                // Сохраняем ID родителя СРАЗУ для корректной проверки видимости
                childrenSvg.setAttribute('data-parent-id', parentId);
                
                // Очищаем предыдущие элементы перед созданием новых
                childrenGroup.innerHTML = '';
                
                console.log('Creating children menu for parent:', parentId, 'children count:', children.length);
                
                // Находим родительский сектор для получения его угла
                const parentSectorGroup = document.querySelector(`.sector-group[data-item-id="${parentId}"]`);
                if (!parentSectorGroup) {
                    console.error('Parent sector not found for id:', parentId);
                    return;
                }
                
                // Получаем угол центра родительского сектора (в градусах относительно вертикали, как в SVG)
                const parentCenterAngle = parseFloat(parentSectorGroup.getAttribute('data-center-angle'));
                
                // Получаем текущий поворот основного SVG
                const mainSvgTransform = mainSvg.style.transform || window.getComputedStyle(mainSvg).transform;
                let currentRotation = 0;
                if (mainSvgTransform && mainSvgTransform !== 'none') {
                    const match = mainSvgTransform.match(/rotate\(([-\d.]+)deg\)/);
                    if (match) {
                        currentRotation = parseFloat(match[1]);
                    }
                }
                
                // Угол индикатора с учетом поворота основного SVG
                const indicatorAngle = parentCenterAngle + currentRotation;
                
                // Центр основного SVG относительно контейнера (270, 270)
                const mainSvgCenterX = 270;
                const mainSvgCenterY = 270;
                
                // Радиус внешнего кольца основного меню (где находятся индикаторы)
                const outerRadius = 250;
                
                // Основное кольцо: innerRadius = 200, outerRadius = 250, ширина = 50px
                // Внешнее кольцо должно иметь такую же ширину (50px)
                // Внешнее кольцо начинается сразу после основного (без отступа)
                const childrenInnerRadius = 250; // Внешний радиус основного кольца (без отступа)
                const childrenOuterRadius = 300; // Внешний радиус: внутренний + 50px (такая же ширина как основное кольцо)
                
                // Вычисляем координаты индикатора относительно контейнера
                // В SVG 0° = верх, поэтому используем (angle + 90) для преобразования в математические координаты
                const indicatorAngleRad = Math.PI * (indicatorAngle + 90) / 180;
                const indicatorX = mainSvgCenterX + outerRadius * Math.cos(indicatorAngleRad);
                const indicatorY = mainSvgCenterY + outerRadius * Math.sin(indicatorAngleRad);
                
                // Индикатор должен быть по центру внутреннего радиуса внешнего кольца
                // То есть: индикатор должен находиться на расстоянии childrenInnerRadius от центра внешнего кольца
                // Направление: от центра основного SVG к индикатору (это направление, в котором должен быть центр внешнего кольца от индикатора)
                
                // Вектор от центра основного SVG к индикатору
                const vectorToIndicatorX = indicatorX - mainSvgCenterX;
                const vectorToIndicatorY = indicatorY - mainSvgCenterY;
                
                // Длина вектора (должна быть равна outerRadius = 250)
                const vectorLength = Math.sqrt(vectorToIndicatorX * vectorToIndicatorX + vectorToIndicatorY * vectorToIndicatorY);
                
                // Нормализованный вектор (единичный вектор в направлении к индикатору)
                const normalizedX = vectorToIndicatorX / vectorLength;
                const normalizedY = vectorToIndicatorY / vectorLength;
                
                // Центр внутреннего круга внешнего кольца должен быть в центре индикатора
                // Индикатор находится на радиусе outerRadius (250) от центра основного SVG
                // Внутренний радиус внешнего кольца childrenInnerRadius = 250
                // Чтобы центр внутреннего радиуса был в центре индикатора, нужно сместить центр внешнего SVG
                // так, чтобы индикатор находился на внутреннем радиусе внешнего кольца
                // То есть: расстояние от центра внешнего SVG до индикатора = childrenInnerRadius (250)
                // Решаем: центр_внешнего_SVG = индикатор - normalized * childrenInnerRadius
                const childrenSvgCenterX = indicatorX - normalizedX * childrenInnerRadius;
                const childrenSvgCenterY = indicatorY - normalizedY * childrenInnerRadius;
                
                // Центр внешнего SVG в его собственных координатах (400, 400 для SVG 800x800)
                // Контейнер имеет размер 540x540, внешний SVG 800x800
                // Текущее смещение внешнего SVG: top: -130px, left: -130px (чтобы центрировать 800x800 относительно 540x540)
                // Это означает, что центр внешнего SVG (400, 400) находится в позиции (270, 270) контейнера
                // Нужно сместить так, чтобы центр был в позиции (childrenSvgCenterX, childrenSvgCenterY)
                const offsetX = childrenSvgCenterX - 270; // Разница между нужной позицией центра и центром контейнера
                const offsetY = childrenSvgCenterY - 270;
                
                // Обновляем позицию внешнего SVG
                childrenSvg.style.top = (-130 + offsetY) + 'px';
                childrenSvg.style.left = (-130 + offsetX) + 'px';
                
                // Логирование для отладки
                console.log('Children menu positioning:', {
                    parentCenterAngle: parentCenterAngle,
                    currentRotation: currentRotation,
                    indicatorAngle: indicatorAngle,
                    indicatorX: indicatorX,
                    indicatorY: indicatorY,
                    childrenSvgCenterX: childrenSvgCenterX,
                    childrenSvgCenterY: childrenSvgCenterY,
                    childrenInnerRadius: childrenInnerRadius,
                    childrenOuterRadius: childrenOuterRadius,
                    normalizedX: normalizedX,
                    normalizedY: normalizedY
                });
                
                // Центр внешнего кольца для расчета секторов в координатах самого внешнего SVG
                const centerX = 400; // Центр внешнего SVG в его собственных координатах
                const centerY = 400;
                const totalChildren = children.length;
                // Для одного элемента используем полный круг, для нескольких - делим поровну
                const childrenAngleStep = totalChildren === 1 ? 360 : 360 / totalChildren;
                const isAuthenticated = @json(auth()->check());
                
                // Поворачиваем внешнее кольцо так, чтобы первый сектор был направлен к индикатору
                // Индикатор находится на внутреннем радиусе внешнего кольца в направлении от центра внешнего SVG
                // Угол от центра внешнего SVG к индикатору в математических координатах (внешнего SVG)
                // В координатах внешнего SVG: индикатор находится на расстоянии childrenInnerRadius от центра
                // в направлении normalized вектора
                const indicatorAngleInSvg = Math.atan2(normalizedY, normalizedX) * 180 / Math.PI;
                
                // Первый сектор начинается с угла -90° в SVG координатах
                // Центр первого сектора: -90° + childrenAngleStep/2 в SVG координатах
                // Преобразуем в математические координаты: (-90° + childrenAngleStep/2) + 90° = childrenAngleStep/2
                const firstSectorCenterAngleMath = childrenAngleStep / 2;
                
                // Поворачиваем внешнее кольцо так, чтобы центр первого сектора совпал с направлением к индикатору
                // В SVG координатах: indicatorAngleInSvg - 90° - (firstSectorCenterAngleMath - 90°)
                // = indicatorAngleInSvg - firstSectorCenterAngleMath
                const rotationAngle = indicatorAngleInSvg - firstSectorCenterAngleMath;
                
                // Применяем поворот к внешнему SVG (с учетом текущего transform-origin)
                childrenSvg.style.transformOrigin = '400px 400px'; // Центр внешнего SVG
                // Применяем только rotate (не добавляем к существующему transform, так как он может мешать)
                childrenSvg.style.transform = `rotate(${rotationAngle}deg)`;
                
                console.log('Children menu rotation:', {
                    indicatorAngleInSvg: indicatorAngleInSvg,
                    firstSectorCenterAngleMath: firstSectorCenterAngleMath,
                    rotationAngle: rotationAngle,
                    normalizedX: normalizedX,
                    normalizedY: normalizedY
                });
                
                // Создаем секторы для дочерних элементов
                children.forEach((child, index) => {
                    let path;
                    let startAngleSvg, endAngleSvg;
                    
                    if (totalChildren === 1) {
                        // Для одного элемента делаем полный круг через две полудуги
                        // Используем верхнюю точку (0° в SVG = 90° в математических) как начальную
                        const topAngleSvg = 0;
                        startAngleSvg = topAngleSvg;
                        endAngleSvg = 360; // Полный круг
                        const topAngle = topAngleSvg + 90; // Преобразуем в математические координаты
                        const topAngleRad = Math.PI * topAngle / 180;
                        
                        // Используем нижнюю точку (180° в SVG = 270° в математических) как промежуточную
                        const bottomAngleSvg = 180;
                        const bottomAngle = bottomAngleSvg + 90; // Преобразуем в математические координаты
                        const bottomAngleRad = Math.PI * bottomAngle / 180;
                        
                        // Точки для верхней части (начало)
                        const innerTopX = centerX + childrenInnerRadius * Math.cos(topAngleRad);
                        const innerTopY = centerY + childrenInnerRadius * Math.sin(topAngleRad);
                        const outerTopX = centerX + childrenOuterRadius * Math.cos(topAngleRad);
                        const outerTopY = centerY + childrenOuterRadius * Math.sin(topAngleRad);
                        
                        // Точки для нижней части (промежуточная)
                        const innerBottomX = centerX + childrenInnerRadius * Math.cos(bottomAngleRad);
                        const innerBottomY = centerY + childrenInnerRadius * Math.sin(bottomAngleRad);
                        const outerBottomX = centerX + childrenOuterRadius * Math.cos(bottomAngleRad);
                        const outerBottomY = centerY + childrenOuterRadius * Math.sin(bottomAngleRad);
                        
                        // Создаем путь для полного круга: две полудуги по 180 градусов
                        path = `M ${innerTopX} ${innerTopY} L ${outerTopX} ${outerTopY} ` +
                               `A ${childrenOuterRadius} ${childrenOuterRadius} 0 1 1 ${outerBottomX} ${outerBottomY} ` +
                               `L ${innerBottomX} ${innerBottomY} ` +
                               `A ${childrenInnerRadius} ${childrenInnerRadius} 0 1 0 ${innerTopX} ${innerTopY} Z`;
                    } else {
                        // Для нескольких элементов - обычный расчет
                        // Углы в SVG координатах (0° = верх)
                        startAngleSvg = (index * childrenAngleStep) - 90;
                        endAngleSvg = ((index + 1) * childrenAngleStep) - 90;
                        // Преобразуем в математические координаты (0° = право): добавляем 90°
                        const startAngle = startAngleSvg + 90;
                        const endAngle = endAngleSvg + 90;
                        const startAngleRad = Math.PI * startAngle / 180;
                        const endAngleRad = Math.PI * endAngle / 180;
                        
                        // Точки для внутреннего радиуса
                        const innerStartX = centerX + childrenInnerRadius * Math.cos(startAngleRad);
                        const innerStartY = centerY + childrenInnerRadius * Math.sin(startAngleRad);
                        const innerEndX = centerX + childrenInnerRadius * Math.cos(endAngleRad);
                        const innerEndY = centerY + childrenInnerRadius * Math.sin(endAngleRad);
                        
                        // Точки для внешнего радиуса
                        const outerStartX = centerX + childrenOuterRadius * Math.cos(startAngleRad);
                        const outerStartY = centerY + childrenOuterRadius * Math.sin(startAngleRad);
                        const outerEndX = centerX + childrenOuterRadius * Math.cos(endAngleRad);
                        const outerEndY = centerY + childrenOuterRadius * Math.sin(endAngleRad);
                        
                        const largeArcFlag = (childrenAngleStep > 180) ? 1 : 0;
                        
                        // Путь для сектора
                        path = `M ${innerStartX} ${innerStartY} L ${outerStartX} ${outerStartY} A ${childrenOuterRadius} ${childrenOuterRadius} 0 ${largeArcFlag} 1 ${outerEndX} ${outerEndY} L ${innerEndX} ${innerEndY} A ${childrenInnerRadius} ${childrenInnerRadius} 0 ${largeArcFlag} 0 ${innerStartX} ${innerStartY} Z`;
                    }
                    
                    // Получаем URL и название
                    const childUrl = buildChildUrl(child, isAuthenticated);
                    const childName = getChildName(child);
                    
                    // Создаем группу для сектора
                    const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                    g.setAttribute('class', 'children-sector-group');
                    g.setAttribute('data-child-index', index);
                    g.setAttribute('data-href', childUrl);
                    
                    // Создаем путь сектора
                    const pathEl = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    pathEl.setAttribute('d', path);
                    pathEl.setAttribute('class', 'children-sector-path');
                    pathEl.setAttribute('stroke-width', '2');
                    // Устанавливаем начальные цвета сразу (будут обновлены в updateChildrenColors)
                    const isDark = document.documentElement.classList.contains('dark') || 
                                  (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
                    if (isDark) {
                        pathEl.setAttribute('fill', '#161615');
                        pathEl.setAttribute('stroke', '#3E3E3A');
                    } else {
                        pathEl.setAttribute('fill', 'white');
                        pathEl.setAttribute('stroke', '#e3e3e0');
                    }
                    g.appendChild(pathEl);
                    
                    // Создаем путь-дугу для текста
                    const textRadius = (childrenInnerRadius + childrenOuterRadius) / 2;
                    let textPathD;
                    const textPathId = `children-text-path-${parentId}-${index}`;
                    
                    if (totalChildren === 1) {
                        // Для одного элемента создаем путь-дугу для полного круга
                        // Используем верх и низ точки
                        const topAngle = 90; // Математический угол для верха
                        const topAngleRad = Math.PI * topAngle / 180;
                        const bottomAngle = 270; // Математический угол для низа
                        const bottomAngleRad = Math.PI * bottomAngle / 180;
                        
                        const topX = centerX + textRadius * Math.cos(topAngleRad);
                        const topY = centerY + textRadius * Math.sin(topAngleRad);
                        const bottomX = centerX + textRadius * Math.cos(bottomAngleRad);
                        const bottomY = centerY + textRadius * Math.sin(bottomAngleRad);
                        
                        // Путь для полного круга: две полудуги
                        textPathD = `M ${topX} ${topY} A ${textRadius} ${textRadius} 0 1 1 ${bottomX} ${bottomY} A ${textRadius} ${textRadius} 0 1 1 ${topX} ${topY}`;
                    } else {
                        // Для нескольких элементов создаем дугу от начала до конца сектора
                        const startAngle = startAngleSvg + 90; // Преобразуем в математические координаты
                        const endAngle = endAngleSvg + 90;
                        const startAngleRad = Math.PI * startAngle / 180;
                        const endAngleRad = Math.PI * endAngle / 180;
                        
                        const textStartX = centerX + textRadius * Math.cos(startAngleRad);
                        const textStartY = centerY + textRadius * Math.sin(startAngleRad);
                        const textEndX = centerX + textRadius * Math.cos(endAngleRad);
                        const textEndY = centerY + textRadius * Math.sin(endAngleRad);
                        
                        const textPathLargeArcFlag = (childrenAngleStep > 180) ? 1 : 0;
                        textPathD = `M ${textStartX} ${textStartY} A ${textRadius} ${textRadius} 0 ${textPathLargeArcFlag} 1 ${textEndX} ${textEndY}`;
                    }
                    
                    // Создаем defs с путем для текста
                    const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
                    const textPathDef = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    textPathDef.setAttribute('id', textPathId);
                    textPathDef.setAttribute('d', textPathD);
                    defs.appendChild(textPathDef);
                    g.appendChild(defs);
                    
                    // Создаем текст с textPath
                    const textEl = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                    textEl.setAttribute('class', 'children-sector-text text-xs font-medium');
                    // Устанавливаем начальный цвет текста
                    if (isDark) {
                        textEl.setAttribute('fill', '#EDEDEC');
                    } else {
                        textEl.setAttribute('fill', '#1b1b18');
                    }
                    
                    const textPathEl = document.createElementNS('http://www.w3.org/2000/svg', 'textPath');
                    textPathEl.setAttribute('href', `#${textPathId}`);
                    textPathEl.setAttribute('startOffset', '50%');
                    textPathEl.setAttribute('text-anchor', 'middle');
                    textPathEl.setAttribute('dominant-baseline', 'middle');
                    textPathEl.textContent = childName;
                    textEl.appendChild(textPathEl);
                    g.appendChild(textEl);
                    
                    // Обработчик клика на сектор дочернего элемента
                    pathEl.addEventListener('click', function(e) {
                        e.stopPropagation();
                        window.location.href = childUrl;
                    });
                    
                    childrenGroup.appendChild(g);
                });
                
                // Обновляем цвета для дочерних элементов
                updateChildrenColors();
                
                console.log('Children menu elements created:', childrenGroup.children.length);
                
                // Проверяем созданные элементы
                if (childrenGroup.children.length > 0) {
                    const firstPath = childrenGroup.children[0].querySelector('path');
                    if (firstPath) {
                        console.log('First path attributes:', {
                            d: firstPath.getAttribute('d'),
                            fill: firstPath.getAttribute('fill'),
                            stroke: firstPath.getAttribute('stroke'),
                            class: firstPath.className.baseVal
                        });
                    }
                }
                
                // Показываем внешнее кольцо - используем несколько способов для гарантии
                childrenSvg.classList.remove('children-menu-hidden');
                childrenSvg.classList.add('show');
                // Удаляем inline style display: none, если он есть
                childrenSvg.style.removeProperty('display');
                childrenSvg.style.display = 'block';
                childrenSvg.style.pointerEvents = 'all';
                childrenSvg.style.opacity = '1';
                childrenSvg.style.visibility = 'visible';
                childrenSvg.style.zIndex = '10';
                childrenSvg.style.zIndex = '10';
                
                // Принудительно обновляем стили через setTimeout для гарантии
                setTimeout(() => {
                    const computedStyle = window.getComputedStyle(childrenSvg);
                    const rect = childrenSvg.getBoundingClientRect();
                    console.log('Children menu visibility check:', {
                        display: computedStyle.display,
                        opacity: computedStyle.opacity,
                        visibility: computedStyle.visibility,
                        classList: childrenSvg.classList.toString(),
                        childrenCount: childrenGroup.children.length,
                        svgWidth: childrenSvg.offsetWidth,
                        svgHeight: childrenSvg.offsetHeight,
                        top: computedStyle.top,
                        left: computedStyle.left,
                        transform: computedStyle.transform,
                        boundingRect: {
                            top: rect.top,
                            left: rect.left,
                            width: rect.width,
                            height: rect.height
                        },
                        containerRect: document.getElementById('circular-menu-container')?.getBoundingClientRect()
                    });
                    
                    // Проверяем видимость элементов
                    const paths = childrenGroup.querySelectorAll('path');
                    if (paths.length > 0) {
                        const firstPathRect = paths[0].getBoundingClientRect();
                        console.log('First path bounding rect:', firstPathRect);
                    }
                }, 50);
                
                // Останавливаем вращение основного кольца при показе подменю
                if (mainSvg) {
                    mainSvg.style.animationPlayState = 'paused';
                }
                
                // Останавливаем вращение SVG индикаторов
                const indicatorsSvg = document.getElementById('indicators-svg');
                if (indicatorsSvg) {
                    indicatorsSvg.style.animationPlayState = 'paused';
                }
                
                // Останавливаем вращение лого (counterRotate анимация)
                @php
                    $logoClassJs = LogoHelper::getClass('center');
                @endphp
                const logo = document.querySelector('.{{ $logoClassJs }}');
                if (logo) {
                    logo.style.animationPlayState = 'paused';
                }
            }
            
            // Функция для скрытия подменю
            function hideChildrenMenu() {
                const mainSvg = document.getElementById('ring-svg');
                const childrenSvg = document.getElementById('children-ring-svg');
                
                if (!childrenSvg) return;
                
                console.log('Hiding children menu - before:', {
                    hasShowClass: childrenSvg.classList.contains('show'),
                    hasHiddenClass: childrenSvg.classList.contains('children-menu-hidden'),
                    display: window.getComputedStyle(childrenSvg).display
                });
                
                // Скрываем внешнее кольцо - используем несколько способов для гарантии
                childrenSvg.classList.remove('show');
                childrenSvg.classList.add('children-menu-hidden');
                // Устанавливаем inline стили для гарантии скрытия
                childrenSvg.style.display = 'none';
                childrenSvg.style.pointerEvents = 'none';
                childrenSvg.style.opacity = '0';
                childrenSvg.style.visibility = 'hidden';
                childrenSvg.removeAttribute('data-parent-id');
                // Сбрасываем transform при скрытии
                childrenSvg.style.transform = '';
                
                console.log('Hiding children menu - after:', {
                    hasShowClass: childrenSvg.classList.contains('show'),
                    hasHiddenClass: childrenSvg.classList.contains('children-menu-hidden'),
                    display: window.getComputedStyle(childrenSvg).display,
                    visibility: window.getComputedStyle(childrenSvg).visibility,
                    opacity: window.getComputedStyle(childrenSvg).opacity
                });
                
                // Возобновляем вращение основного кольца
                if (mainSvg) {
                    mainSvg.style.animationPlayState = 'running';
                }
                
                // Возобновляем вращение SVG индикаторов
                const indicatorsSvg = document.getElementById('indicators-svg');
                if (indicatorsSvg) {
                    indicatorsSvg.style.animationPlayState = 'running';
                }
                
                // Возобновляем вращение лого (counterRotate анимация)
                @php
                    $logoClassJs = LogoHelper::getClass('center');
                @endphp
                const logo = document.querySelector('.{{ $logoClassJs }}');
                if (logo) {
                    logo.style.animationPlayState = 'running';
                }
            }
            
            // Функция для построения URL дочернего элемента
            function buildChildUrl(child, isAuthenticated) {
                if (typeof child === 'object' && child !== null) {
                    // Если есть готовый URL, используем его
                    if (child.url) {
                        return child.url;
                    }
                    // Иначе строим URL из параметров
                    if (!isAuthenticated && child.guest_href) {
                        if (child.guest_href_params) {
                            // Извлекаем ID из параметров
                            const params = child.guest_href_params;
                            const id = params.id || params.type_id || params[Object.keys(params)[0]];
                            if (id) {
                                return '/resources/type/' + id;
                            }
                        }
                        return '#';
                    } else if (child.href) {
                        if (child.href_params) {
                            // Извлекаем ID из параметров
                            const params = child.href_params;
                            const id = params.id || params.type_id || params[Object.keys(params)[0]];
                            if (id) {
                                return '/resources/type/' + id;
                            }
                        }
                        return '#';
                    }
                }
                return '#';
            }
            
            // Функция для получения названия дочернего элемента
            function getChildName(child) {
                if (typeof child === 'object' && child !== null) {
                    // Если есть переведенное название, используем его
                    if (child.translated_name) {
                        return child.translated_name;
                    }
                    // Иначе используем исходное название
                    return child.name || '';
                }
                return '';
            }
            
            // Функция для обновления цветов дочерних элементов
            function updateChildrenColors() {
                const hasDarkClass = document.documentElement.classList.contains('dark');
                const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                const hasLightClass = document.documentElement.classList.contains('light');
                const isDark = hasDarkClass || (prefersDark && !hasLightClass);
                
                const childrenPaths = document.querySelectorAll('.children-sector-path');
                childrenPaths.forEach(path => {
                    if (isDark) {
                        path.setAttribute('fill', '#161615');
                        path.setAttribute('stroke', '#3E3E3A');
                    } else {
                        path.setAttribute('fill', 'white');
                        path.setAttribute('stroke', '#e3e3e0');
                    }
                });
                
                const childrenTexts = document.querySelectorAll('.children-sector-text');
                childrenTexts.forEach(text => {
                    if (isDark) {
                        text.setAttribute('fill', '#EDEDEC');
                    } else {
                        text.setAttribute('fill', '#1b1b18');
                    }
                });
            }
            
            // Добавляем обновление цветов дочерних элементов при изменении темы
            const childrenColorObserver = new MutationObserver(function(mutations) {
                updateChildrenColors();
            });
            childrenColorObserver.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class']
            });
            
            // Слушатель изменения системной темы для дочерних элементов
            if (window.matchMedia) {
                const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
                if (mediaQuery.addEventListener) {
                    mediaQuery.addEventListener('change', updateChildrenColors);
                } else if (mediaQuery.addListener) {
                    mediaQuery.addListener(updateChildrenColors);
                }
            }
        </script>

        @if (Route::has('login'))
            <div class="h-14.5 hidden lg:block"></div>
        @endif
    </body>
</html>
