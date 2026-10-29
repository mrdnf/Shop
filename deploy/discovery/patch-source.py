from pathlib import Path
import argparse, hashlib, json, re

def patch(site,base):
    base=Path(base); changed=[]
    if site['domain'] in ['pos.syseagles.com','migrate.syseagles.com','invoicing.syseagles.com']:
        path=base/'index.php';text=path.read_text(encoding='utf-8-sig')
        if '$seoRequestPath' not in text:
            guard=r'''
$seoRequestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$seoBasePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
$seoRelativePath = $seoBasePath !== '' && str_starts_with($seoRequestPath, $seoBasePath.'/')
    ? substr($seoRequestPath, strlen($seoBasePath)) : $seoRequestPath;
if (!in_array($seoRelativePath, ['/', '/index.php', $seoBasePath], true)) {
    http_response_code(404);
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><title>Page not found</title><h1>Page not found</h1><p><a href="./">Open the application</a></p></html>';
    exit;
}
'''
            text=text.replace('<?php','<?php\n'+guard,1)
            path.write_text(text,encoding='utf-8',newline='\n');changed.append('index.php')
    for relative in site['php_heads']:
        path=base/relative
        if not path.exists():continue
        text=path.read_text(encoding='utf-8-sig')
        if '<!-- syseagles-public-discovery -->' in text:continue
        depth=len(Path(relative).parts)-1
        root=f'dirname(__DIR__, {depth})' if depth else '__DIR__'
        hook=f"<?php require {root}.'/deploy/discovery/php-head.php'; ?>"
        if path.suffix=='.php' and path.name.endswith('.blade.php'):hook="@php require base_path('deploy/discovery/php-head.php'); @endphp"
        if '</head>' not in text:raise RuntimeError(f'No head closing tag: {path}')
        text=text.replace('</head>','<!-- syseagles-public-discovery -->\n'+hook+'\n</head>',1)
        path.write_text(text,encoding='utf-8',newline='\n');changed.append(relative)
    if site['domain']=='coding.syseagles.com':
        for route,page in site['pages'].items():
            if route=='/about/':continue
            relative='index.html' if route=='/' else route.lstrip('/')
            path=base/relative
            if not path.exists():continue
            text=path.read_text(encoding='utf-8-sig')
            if '<!-- syseagles-public-discovery -->' in text:continue
            head=(base/'deploy/discovery/public/head'/(hashlib.sha256(route.encode()).hexdigest()+'.html')).read_text(encoding='utf-8')
            text=text.replace('</head>','<!-- syseagles-public-discovery -->\n'+head+'\n</head>',1)
            path.write_text(text,encoding='utf-8',newline='\n');changed.append(relative)
    if site['domain']=='quiz.syseagles.com':
        for relative in ['client/index.html','client/dist/index.html']:
            path=base/relative
            if not path.exists():continue
            text=path.read_text(encoding='utf-8-sig')
            if '<!-- syseagles-public-discovery -->' in text:continue
            head=(base/'deploy/discovery/public/head'/(hashlib.sha256(b'/').hexdigest()+'.html')).read_text(encoding='utf-8')
            from html import escape
            head='<meta name="description" content="'+escape(site['description'])+'">\n'+head
            text=text.replace('</head>','<!-- syseagles-public-discovery -->\n'+head+'\n</head>',1)
            fallback='<section aria-label="About Classivo Quiz Master"><h1>Classivo Quiz Master</h1><p>'+escape(site['description'])+'</p><p><a href="/about/">Product information</a> · <a href="/login">Candidate sign-in</a></p></section>'
            text=text.replace('<div id="root"></div>','<div id="root">'+fallback+'</div>',1)
            path.write_text(text,encoding='utf-8',newline='\n');changed.append(relative)
    return changed

if __name__=='__main__':
    parser=argparse.ArgumentParser();parser.add_argument('--manifest',default='site-map.json');parser.add_argument('--key');parser.add_argument('--base');args=parser.parse_args()
    config_path=Path(args.manifest)
    if not config_path.exists() and (Path(__file__).parent/'site.json').exists():config_path=Path(__file__).parent/'site.json'
    config=json.loads(config_path.read_text(encoding='utf-8'))
    manifest={config['domain'].split('.')[0]:config} if 'domain' in config else config
    for key,site in manifest.items():
        if args.key and key!=args.key:continue
        paths=[args.base] if args.base else ([site['local'],*site.get('mirrors',[])] if 'local' in site else [str(Path(__file__).resolve().parents[2])])
        for base in paths:print(key,base,json.dumps(patch(site,base)))
