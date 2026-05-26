<?php
/**
 * Plugin Name: Books Manager
 * Description: Display books restricted to logged-in users.

 */

if ( ! defined( 'ABSPATH' ) ) exit;

/*  1. CUSTOM POST TYPE  */
add_action( 'init', function () {
    register_post_type( 'books', [
        'label'       => 'Books',
        'public'      => true,
        'has_archive' => true,
        'rewrite'     => [ 'slug' => 'books' ],
        'supports'    => [ 'title', 'editor' ],
        'menu_icon'   => 'dashicons-book',
    ] );
} );

/* 2. META BOX  */
add_action( 'add_meta_boxes', function () {
    add_meta_box( 'book_details', 'Book Details', 'bm_meta_box', 'books' );
} );

function bm_meta_box( $post ) {
    $author = get_post_meta( $post->ID, '_bm_author', true );
    $genre  = get_post_meta( $post->ID, '_bm_genre',  true );
    $date   = get_post_meta( $post->ID, '_bm_date',   true );
    $genres = [ 'Fiction', 'Non-Fiction', 'Sci-Fi', 'Fantasy', 'Mystery', 'Biography', 'Horror', 'Romance' ];
    ?>
    <p>
        <label>Author</label><br>
        <input type="text" name="bm_author" value="<?php echo esc_attr( $author ); ?>" style="width:100%">
    </p>
    <p>
        <label>Genre</label><br>
        <select name="bm_genre" style="width:100%">
            <option value="">— Select Genre —</option>
            <?php foreach ( $genres as $g ) : ?>
                <option value="<?php echo esc_attr($g); ?>" <?php selected( $genre, $g ); ?>><?php echo esc_html($g); ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <label>Published Date</label><br>
        <input type="date" name="bm_date" value="<?php echo esc_attr( $date ); ?>">
    </p>
    <?php
}

/*  3. SAVE META  */
add_action( 'save_post', function ( $post_id ) {
    if ( get_post_type( $post_id ) !== 'books' ) return;
    if ( isset( $_POST['bm_author'] ) ) update_post_meta( $post_id, '_bm_author', sanitize_text_field( $_POST['bm_author'] ) );
    if ( isset( $_POST['bm_genre'] ) )  update_post_meta( $post_id, '_bm_genre',  sanitize_text_field( $_POST['bm_genre'] ) );
    if ( isset( $_POST['bm_date'] ) )   update_post_meta( $post_id, '_bm_date',   sanitize_text_field( $_POST['bm_date'] ) );
} );

/*  4. RESTRICT ACCESS  */
add_action( 'template_redirect', function () {
    if ( is_user_logged_in() ) return;

    if ( is_singular( 'books' ) || is_post_type_archive( 'books' ) ) {
        wp_redirect( wp_login_url( get_permalink() ) );
        exit;
    }

    global $post;
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'books_list' ) ) {
        wp_redirect( wp_login_url( get_permalink() ) );
        exit;
    }
} );

/*  5. ENQUEUE CSS */
add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style( 'bm-style', plugin_dir_url( __FILE__ ) . 'assets/css/style.css' );
} );

/* 6. AJAX LOAD BOOKS*/
add_action( 'wp_ajax_bm_load_books',        'bm_load_books' );
add_action( 'wp_ajax_nopriv_bm_load_books', 'bm_load_books' );

function bm_load_books() {
    if ( ! is_user_logged_in() ) wp_send_json_error( 'Login required.' );

    $paged  = isset( $_POST['page'] )   ? absint( $_POST['page'] )               : 1;
    $genre  = isset( $_POST['genre'] )  ? sanitize_text_field( $_POST['genre'] ) : '';
    $author = isset( $_POST['author'] ) ? sanitize_text_field( $_POST['author']) : '';
    $search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search']) : '';

    $args = [
        'post_type'      => 'books',
        'posts_per_page' => 5,
        'paged'          => $paged,
        's'              => $search,
    ];

    $meta_query = [];
    if ( $genre )  $meta_query[] = [ 'key' => '_bm_genre',  'value' => $genre,  'compare' => '=' ];
    if ( $author ) $meta_query[] = [ 'key' => '_bm_author', 'value' => $author, 'compare' => 'LIKE' ];
    if ( $meta_query ) $args['meta_query'] = array_merge( [ 'relation' => 'AND' ], $meta_query );

    $query = new WP_Query( $args );
    $books = [];

    while ( $query->have_posts() ) {
        $query->the_post();
        $date    = get_post_meta( get_the_ID(), '_bm_date', true );
        $books[] = [
            'title'   => get_the_title(),
            'link'    => get_permalink(),
            'author'  => get_post_meta( get_the_ID(), '_bm_author', true ),
            'genre'   => get_post_meta( get_the_ID(), '_bm_genre',  true ),
            'date'    => $date ? date_i18n( get_option('date_format'), strtotime($date) ) : '',
            'excerpt' => get_the_excerpt(),
        ];
    }

    wp_reset_postdata();
    wp_send_json_success( [ 'books' => $books, 'max' => (int) $query->max_num_pages, 'total' => (int) $query->found_posts, 'current' => $paged ] );
}

/*  7. SHORTCODE  */
add_shortcode( 'books_list', function () {
    if ( ! is_user_logged_in() ) {
        return '<p class="bm-login-notice">You must be logged in to view this content. <a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">Log in or Register</a></p>';
    }

    $genres = [ 'Fiction', 'Non-Fiction', 'Sci-Fi', 'Fantasy', 'Mystery', 'Biography', 'Horror', 'Romance' ];
    ob_start();
    ?>
    <div class="bm-wrapper">
        <h1 class="bm-title">📚 Books Library</h1>

        <div class="bm-filters">
            <input type="text" id="bm-search" class="bm-input" placeholder="Search by title…">
            <select id="bm-genre" class="bm-input">
                <option value="">All Genres</option>
                <?php foreach ( $genres as $g ) : ?>
                    <option value="<?php echo esc_attr($g); ?>"><?php echo esc_html($g); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="bm-author" class="bm-input" placeholder="Filter by author…">
            <button id="bm-filter-btn" class="bm-btn">Filter</button>
            <button id="bm-reset-btn" class="bm-btn bm-btn-ghost">Reset</button>
        </div>

        <p id="bm-status" class="bm-status"></p>
        <div id="bm-grid"></div>
        <div id="bm-pagination"></div>
    </div>

    <script>
    (function () {
        const AJAX = "<?php echo admin_url('admin-ajax.php'); ?>";
        const grid = document.getElementById('bm-grid');
        const pag  = document.getElementById('bm-pagination');
        const stat = document.getElementById('bm-status');

        function loadBooks(page) {
            grid.innerHTML = '<p>Loading…</p>';
            pag.innerHTML  = '';

            const body = new FormData();
            body.append('action', 'bm_load_books');
            body.append('page',   page);
            body.append('search', document.getElementById('bm-search').value.trim());
            body.append('genre',  document.getElementById('bm-genre').value);
            body.append('author', document.getElementById('bm-author').value.trim());

            fetch(AJAX, { method: 'POST', body })
                .then(r => r.json())
                .then(({ success, data }) => {
                    if (!success || !data.books.length) {
                        grid.innerHTML = '<p class="bm-empty">No books found.</p>';
                        return;
                    }

                    stat.textContent = `Showing ${data.books.length} of ${data.total} books`;

                    grid.innerHTML = data.books.map(b => `
                        <article class="bm-card">
                            <div class="bm-card-icon">📖</div>
                            <div class="bm-card-body">
                                <h2><a href="${b.link}">${b.title}</a></h2>
                                <p><span class="bm-badge">${b.genre || '—'}</span> ${b.date ? '📅 ' + b.date : ''}</p>
                                <p>✍️ <strong>${b.author || '—'}</strong></p>
                                <p>${b.excerpt || ''}</p>
                                <a href="${b.link}" class="bm-read-more">Read More →</a>
                            </div>
                        </article>`).join('');

                    if (data.max > 1) {
                        let html = '';
                        if (data.current > 1)        html += `<button class="bm-page-btn" data-p="${data.current - 1}">← Prev</button>`;
                        for (let i = 1; i <= data.max; i++) html += `<button class="bm-page-btn ${i === data.current ? 'active':''}" data-p="${i}">${i}</button>`;
                        if (data.current < data.max) html += `<button class="bm-page-btn" data-p="${data.current + 1}">Next →</button>`;
                        pag.innerHTML = html;
                        pag.querySelectorAll('.bm-page-btn').forEach(btn => btn.addEventListener('click', () => loadBooks(+btn.dataset.p)));
                    }
                });
        }

        document.getElementById('bm-filter-btn').addEventListener('click', () => loadBooks(1));
        document.getElementById('bm-reset-btn').addEventListener('click', () => {
            document.getElementById('bm-search').value = '';
            document.getElementById('bm-genre').value  = '';
            document.getElementById('bm-author').value = '';
            loadBooks(1);
        });
        document.getElementById('bm-search').addEventListener('keydown', e => { if (e.key === 'Enter') loadBooks(1); });

        loadBooks(1);
    })();
    </script>
    <?php
    return ob_get_clean();
} );

/*  8. SINGLE BOOK TEMPLATE */
add_filter( 'single_template', function ( $template ) {
    global $post;
    if ( 'books' === get_post_type( $post ) ) {
        $t = plugin_dir_path( __FILE__ ) . 'templates/single-books.php';
        if ( file_exists( $t ) ) return $t;
    }
    return $template;
} );