@servers(['web' => '127.0.0.1'])

@setup
    $repository = 'git@gitlab.com:<gitlab_username>/repo.git';
    $releases_dir = '/var/www/<project>/releases';
    $app_dir = '/var/www/<project>';
    $release = date('YmdHis');
    $new_release_dir = $releases_dir .'/'. $release;
@endsetup

@story('deploy')
    clone_repository
    composer
    symlinks
@endstory

@task('clone_repository', ['on' => $on])
    echo 'Cloning repository'
    [ -d {{ $releases_dir }} ] || mkdir {{ $releases_dir }}
    git clone --depth 1 {{ $repository }} {{ $new_release_dir }}
    {{--cd {{ $releases_dir }}--}}
    {{--git reset --hard {{ $commit }}--}}
@endtask

@task('composer',['on' =>$on])
    echo "Starting deployment ({{ $release }})"
    cd {{ $new_release_dir }}
    composer install
    php artisan key:generate
    php artisan migrate
    php artisan config:cache
    php artisan route:clear
    php artisan config:clear
    php artisan cache:clear

@endtask

@task('symlinks', ['on' => $on])
    echo "Linking storage directory"
    rm -rf {{ $new_release_dir }}/storage
    ln -nfs {{ $app_dir }}/storage {{ $new_release_dir }}/storage

    echo 'Linking .env file'
    ln -nfs {{ $app_dir }}/.env {{ $new_release_dir }}/.env

    echo 'Linking current release'
    ln -nfs {{ $new_release_dir }} {{ $app_dir }}/current
@endtask
