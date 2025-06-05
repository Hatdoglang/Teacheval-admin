<?php include '../db_connect.php'; ?>
<?php
if (isset($_GET['id'])) {
    // Fetch faculty details
    $qry = $conn->query("SELECT *, CONCAT(firstname, ' ', lastname) AS name FROM faculty_list WHERE id = " . $_GET['id'])->fetch_array();
    foreach ($qry as $k => $v) {
        $$k = $v;
    }

    // Get the average rating
    $avg_query = $conn->query(
        "SELECT AVG(rate) AS average_rating
        FROM evaluation_answers
        JOIN evaluation_list ON evaluation_list.evaluation_id = evaluation_answers.evaluation_id
        WHERE evaluation_list.faculty_id = " . $_GET['id']
    );
    $avg_result = $avg_query->fetch_assoc();
    $average_rating = round($avg_result['average_rating'] ?? 0, 2);

    // Get the total number of reviews and the rating distribution
    $rating_query = $conn->query(
        "SELECT rate, COUNT(*) AS count
        FROM evaluation_answers
        JOIN evaluation_list ON evaluation_list.evaluation_id = evaluation_answers.evaluation_id
        WHERE evaluation_list.faculty_id = " . $_GET['id'] . "
        GROUP BY rate"
    );
    $rating_data = [];
    $total_students = 0;
    while ($row = $rating_query->fetch_assoc()) {
        $rating_data[$row['rate']] = $row['count'];
        $total_students += $row['count'];
    }

    // Ensure all ratings from 1 to 5 are represented
    for ($i = 1; $i <= 5; $i++) {
        if (!isset($rating_data[$i])) {
            $rating_data[$i] = 0;
        }
    }

    // Calculate rating percentages
    $rating_percentages = [];
    foreach ($rating_data as $rate => $count) {
        $rating_percentages[$rate] = $total_students > 0 ? round(($count / $total_students) * 100, 2) : 0;
    }

    // Determine the review status
    $status = 'Negative';
    if ($average_rating >= 4) {
        $status = 'Positive';
    } elseif ($average_rating >= 3) {
        $status = 'Average';
    }

    // Fetch the academic year
    $academic_year_query = $conn->query("SELECT year FROM academic_list WHERE id = " . $_GET['id']);
    $academic_year = $academic_year_query->fetch_assoc()['year'] ?? 'N/A';

    // Fetch comments for sentiment analysis (excluding empty comments)
    $comments_query = $conn->query(
        "SELECT TRIM(evaluation_answers.comments) AS comments
        FROM evaluation_answers
        JOIN evaluation_list ON evaluation_list.evaluation_id = evaluation_answers.evaluation_id
        WHERE evaluation_list.faculty_id = " . $_GET['id'] . "
        HAVING comments IS NOT NULL AND comments != ''"
    );

    $comments = [];
    while ($row = $comments_query->fetch_assoc()) {
        $comments[] = $row['comments'];
    }

    // Stop words to be removed before analysis
    $stop_words = ['the', 'is', 'which', 'on', 'to', 'for', 'with', 'a', 'an', 'of', 'he', 'she', 'his'];

    // Positive and negative phrases for context-aware sentiment analysis
    $positive_words = [
        // English positive phrases\
        'very',
        'excellent teacher',
        'great teaching',
        'amazing teacher',
        'very good',
        'outstanding teacher',
        'best teaching experience',
        'motivates students',
        'effective teaching style',
        'passionate about teaching',
        'encourages learning',
        'supportive and kind',
        'explains concepts well',
        'dedicated to students',
        'engaging lessons',
        'makes learning enjoyable',
        'caring and patient',
        'knowledgeable and professional',
        'inspiring educator',
        'respectful to students',
        'helpful and approachable',
        'explains lessons clearly',  // Added
        'good in teaching',  // Added
        'good and approachable teacher',  // Added
        'good at teaching',  // Added
        'good teacher',
        'nice teacher',

        // Tagalog positive phrases
        'magaling na guro',
        'mahusay magturo',
        'kahanga-hangang guro',
        'napakabuti',
        'natatanging guro',
        'pinakamahusay na karanasan sa pagtuturo',
        'nagpapalakas ng loob ng estudyante',
        'epektibong paraan ng pagtuturo',
        'may malasakit sa pagtuturo',
        'hinihikayat ang pagkatuto',
        'maunawain at mabait',
        'mahusay magpaliwanag',
        'dedikado sa mga estudyante',
        'nakakaengganyong leksyon'
        'Maayu siya mutudlo',
        'Permi on time siya muabot',
        'Okay rapud siya mutudlo'
    ];


    $negative_words = [
        'bad teaching',
        'bad in teaching',
        'bad teacher',
        'not good',
        'bad at',
        'very bad',
        'poor teacher',
        'horrible teacher',
        'terrible teacher',
        'subpar teacher',
        'boring lessons',
        'unhelpful attitude',
        'disorganized lectures',
        'does not explain well',
        'lack of enthusiasm',
        'strict and unapproachable',
        'disrespectful to students',
        'unfair grading',
        'hard to understand',
        // Tagalog negative phrases
        'hindi magaling magturo',
        'hindi maganda',
        'mahirap intindihin',
        'masamang guro',
        'hindi mahusay sa pagtuturo',
        'pangit ang pagtuturo',
        'hindi maayos magpaliwanag',
        'walang sigla sa pagtuturo',
        'masyadong mahigpit',
        'hindi patas sa pagbibigay ng grado',
        'hindi pinapansin ang estudyante',
        'walang pakialam sa mga estudyante'
    ];


    // Function to correct spelling using Levenshtein distance
    function correct_spelling($input, $dictionary)
    {
        $words = explode(' ', strtolower($input)); // Convert input to lowercase & split into words
        $corrected_words = [];

        foreach ($words as $word) {
            $closest_match = find_closest_word($word, $dictionary);
            $corrected_words[] = $closest_match ?? $word; // Use closest match if found
        }

        return implode(' ', $corrected_words); // Reconstruct corrected sentence
    }



    // Function to find the closest word match
    function find_closest_word($word, $dictionary)
    {
        $shortest_distance = PHP_INT_MAX;
        $closest_word = null;

        foreach ($dictionary as $dict_word) {
            $lev_distance = levenshtein($word, $dict_word);
            if ($lev_distance < $shortest_distance) {
                $shortest_distance = $lev_distance;
                $closest_word = $dict_word;
            }
        }

        return ($shortest_distance <= 2) ? $closest_word : null; // Only correct if close match
    }


    function handle_phrases($sentence, $positive_words, $negative_words, $stop_words)
    {
        $sentence_lower = strtolower($sentence);

        // Merge positive and negative words into one dictionary
        $all_words = array_merge($positive_words, $negative_words);

        // Fix spelling
        $corrected_sentence = correct_spelling($sentence_lower, $all_words);


        // Remove stop words
        $words = explode(' ', $corrected_sentence);
        $filtered_words = array_diff($words, $stop_words);
        $filtered_sentence = implode(' ', $filtered_words);

        // Check for exact negative phrases first
        foreach ($negative_words as $phrase) {
            if (stripos($filtered_sentence, $phrase) !== false) {
                return ['negative', $phrase];
            }
        }

        // Check for exact positive phrases
        foreach ($positive_words as $phrase) {
            if (stripos($filtered_sentence, $phrase) !== false) {
                return ['positive', $phrase];
            }
        }

        return ['neutral', null]; // Default to neutral if no matches
    }




    // Analyze sentiment of each comment
    $positive_count = 0;
    $negative_count = 0;
    $unique_comments = array_unique($comments);

    foreach ($unique_comments as $comment) {
        list($sentiment, $phrase) = handle_phrases($comment, $positive_words, $negative_words, $stop_words);
        if ($sentiment == 'positive') {
            $positive_count++;
        } elseif ($sentiment == 'negative') {
            $negative_count++;
        }
    }

    // Determine overall sentiment
    if ($positive_count > $negative_count) {
        $sentiment = 'Positive';
    } elseif ($negative_count > $positive_count) {
        $sentiment = 'Negative';
    } else {
        $sentiment = 'Neutral';
    }
}
?>


<style>
    #uni_modal .modal-footer {
        display: none;
    }

    #uni_modal .modal-footer.display {
        display: flex;
        margin-left: 300px;
    }

    #uni_modal .modal-content {

        width: 1000px;
        margin-left: -220px;
    }

    @media (max-width: 768px) {
        #uni_modal .modal-content {
            width: 95%;
            /* Increase width for smaller screens */
            margin-left: 0;
            /* Remove negative margin */
        }

        #uni_modal .modal-footer.display {
            margin-left: 0;
        }
    }

    .chart-container {
        width: 100%;
        max-width: 1000px;
        /* Adjust as needed */
        overflow-x: auto;
        /* Enables horizontal scrolling if necessary */
    }

    canvas {
        width: 100% !important;
        height: auto !important;
    }
</style>


<div class="container-fluid" style="max-width: 900px; margin: 0 auto;">
    <div class="row">
        <!-- User Profile Card -->
        <div class="col-md-6">
            <div class="card card-widget widget-user shadow" style="width: 100%;">
                <div class="widget-user-header bg-dark">
                    <h3 class="widget-user-username"><?php echo ucwords($name) ?></h3>
                    <h5 class="widget-user-desc"><?php echo $email ?></h5>
                </div>
                <div class="widget-user-image">
                    <?php if (empty($avatar) || (!empty($avatar) && !is_file('../assets/uploads/' . $avatar))): ?>
                        <span
                            class="brand-image img-circle elevation-2 d-flex justify-content-center align-items-center bg-primary text-white font-weight-500"
                            style="width: 90px; height: 90px;">
                            <h5><?php echo strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1)) ?></h5>
                        </span>
                    <?php else: ?>
                        <img class="img-circle elevation-2" src="assets/uploads/<?php echo $avatar ?>" alt="User Avatar"
                            style="width: 90px; height: 90px; object-fit: cover;">
                    <?php endif ?>
                </div>
                <div class="card-footer">
                    <div class="container-fluid">
                        <div class="row">
                            <!-- Label-Value Pair for School Year -->
                            <div class="col-md-6" style="display: none;">
                                <dl>
                                    <dt>School Year</dt>
                                    <dd><?php echo $academic_year ?></dd>
                                </dl>
                            </div>
                            <!-- Label-Value Pair for Status -->
                            <div class="col-md-6" style="display: none;">
                                <dl>
                                    <dt>Status</dt>
                                    <dd>
                                        <span
                                            class="badge <?php echo ($status == 'Positive') ? 'badge-success' : (($status == 'Average') ? 'badge-warning' : 'badge-danger') ?>">
                                            <?php echo $status ?>
                                        </span>
                                    </dd>
                                </dl>
                            </div>
                            <!-- Label-Value Pair for Average Rating -->
                            <div class="col-md-6">
                                <dl>
                                    <dt>Average Rating</dt>
                                    <dd><?php echo $average_rating ?> / 5</dd>
                                </dl>
                            </div>
                            <!-- Label-Value Pair for Total Students -->
                            <div class="col-md-6">
                                <dl>
                                    <dt>Total Students</dt>
                                    <dd><?php echo $total_students; ?></dd>
                                </dl>
                            </div>


                        </div>
                    </div>


                </div>
            </div>
        </div>

        <!-- Sentiment Analysis Card -->
        <div class="col-md-6">
            <div class="card shadow" style="width: 100%;">
                <div class="card-header bg-dark text-white">
                    <h5>Sentiment Analysis</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Initialize arrays for comments
                    $positive_comments = [];
                    $negative_comments = [];
                    $neutral_comments = [];

                    foreach ($unique_comments as $comment) {
                        list($comment_sentiment, $matched_phrase) = handle_phrases($comment, $positive_words, $negative_words, $stop_words);

                        $comment_sentiment = trim(strtolower($comment_sentiment));

                        if ($comment_sentiment === 'positive') {
                            $positive_comments[] = $comment;
                        } elseif ($comment_sentiment === 'negative') {
                            $negative_comments[] = $comment;
                        } else {
                            $neutral_comments[] = $comment;
                        }
                    }

                    // Count each sentiment category
                    $positive_count = count($positive_comments);
                    $negative_count = count($negative_comments);
                    $neutral_count = count($neutral_comments);

                    // Determine overall sentiment
                    $overall_sentiment = ($positive_count > $negative_count) ? 'Positive' :
                        (($negative_count > $positive_count) ? 'Negative' : 'Neutral');
                    ?>

                    <dl>
                        <!-- <dt>Positive Comments</dt>
                        <dd><?php echo $positive_count; ?></dd>
                        <dt>Negative Comments</dt>
                        <dd><?php echo $negative_count; ?></dd>
                        <dt>Negative Comments</dt> -->
                        <!-- <dd><?php echo $neutral_count; ?></dd> -->
                        <?php if ($positive_count > 0 || $negative_count > 0): ?>
                            <dl>


                                <dt>Overall Sentiment</dt>
                                <dd>
                                    <span class="badge 
                <?php echo ($overall_sentiment == 'Positive') ? 'bg-success' :
                    (($overall_sentiment == 'Negative') ? 'bg-danger' : 'bg-warning'); ?>">
                                        <?php echo $overall_sentiment; ?>
                                    </span>
                                </dd>
                            </dl>
                        <?php endif; ?>

                    </dl>

                    <!-- Buttons to Show Comments -->
                    <div class="text-center">
                        <button class="btn btn-success btn-sm m-1" onclick="showComments('positive')">
                            Positive (<?php echo $positive_count; ?>)
                        </button>
                        <button class="btn btn-danger btn-sm m-1" onclick="showComments('negative')">
                            Negative (<?php echo $negative_count; ?>)
                        </button>
                        <button class="btn btn-warning btn-sm m-1" onclick="showComments('neutral')">
                            Neutral (<?php echo $neutral_count; ?>)
                        </button>
                    </div>

                    <!-- Comments Display Section -->
                    <div id="comments-section" class="mt-3" style="max-height: 300px; overflow-y: auto;"></div>

                    <!-- Hidden comments data -->
                    <script>
                        var positiveComments = <?php echo json_encode($positive_comments); ?>;
                        var negativeComments = <?php echo json_encode($negative_comments); ?>;
                        var neutralComments = <?php echo json_encode($neutral_comments); ?>;
                    </script>

                </div>
            </div>
        </div>


        <!-- Rating Chart -->
        <div class="card-body">
            <div class="card-header bg-dark text-white">
                <h5>Rating Distribution</h5>
            </div>
            <canvas id="ratingChart"></canvas>
        </div>

    </div>
</div>

<div class="modal-footer display p-0 m-0">
    <div id="loadingSpinner" class="spinner-border text-primary" role="status" style="display: none;">
        <span class="visually-hidden">Loading...</span>
    </div>
    <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="reloadPage();">Close</button>
</div>

<script>
    function showComments(type) {
        let commentsContainer = document.getElementById('comments-section');
        let commentsList = [];
        let bgColor = '';

        if (type === 'positive') {
            commentsList = positiveComments;
            bgColor = 'bg-success';
        } else if (type === 'negative') {
            commentsList = negativeComments;
            bgColor = 'bg-danger';
        } else {
            commentsList = neutralComments;
            bgColor = 'bg-warning';
        }

        if (commentsList.length === 0) {
            commentsContainer.innerHTML = `<div class="alert alert-light text-center">No ${type} comments available.</div>`;
            return;
        }

        let commentsHTML = `<div class="card shadow">
            <div class="card-header ${bgColor} text-white">
                ${type.charAt(0).toUpperCase() + type.slice(1)} Comments
            </div>
            <div class="card-body">
                <ul class="list-group">`;

        commentsList.forEach(comment => {
            commentsHTML += `<li class="list-group-item">${comment}</li>`;
        });

        commentsHTML += `</ul></div></div>`;

        commentsContainer.innerHTML = commentsHTML;
    }
    function reloadPage() {
        // Show the spinner
        document.getElementById("loadingSpinner").style.display = "inline-block";

        // Reload the page after a short delay (1 second for effect)
        setTimeout(function() {
            location.reload();
        }, 1000);
    }
</script>

<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    var ctx = document.getElementById('ratingChart').getContext('2d');
    var ratingData = <?php echo json_encode($rating_data); ?>;

    var ratingChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Ratings'], // Single category for grouped bars
            datasets: [
                {
                    label: 'Strongly Agree (5 Stars)',
                    data: [ratingData[5]],
                    backgroundColor: 'rgba(75, 192, 75, 0.8)', // Green
                    borderColor: 'rgba(75, 192, 75, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Agree (4 Stars)',
                    data: [ratingData[4]],
                    backgroundColor: 'rgba(54, 162, 235, 0.8)', // Blue
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Uncertain (3 Stars)',
                    data: [ratingData[3]],
                    backgroundColor: 'rgba(255, 206, 86, 0.8)', // Yellow
                    borderColor: 'rgba(255, 206, 86, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Disagree (2 Stars)',
                    data: [ratingData[2]],
                    backgroundColor: 'rgba(255, 165, 0, 0.8)', // Orange
                    borderColor: 'rgba(255, 165, 0, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Strongly Disagree (1 Star)',
                    data: [ratingData[1]],
                    backgroundColor: 'rgba(255, 99, 132, 0.8)', // Red
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',  // Legend on top
                    labels: {
                        color: 'black',
                        font: {
                            size: 14,
                            weight: 'bold'
                        },
                        padding: 15,
                        boxWidth: 20 // Adjust legend box size
                    }
                },
                tooltip: {
                    enabled: false // Disable hover tooltips
                }
            },
            hover: {
                mode: null // Completely disable hover effects
            },
            scales: {
                x: {
                    stacked: true,
                    title: {
                        display: true,
                        text: "Rating Scale"
                    }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: "Number of Reviews"
                    }
                }
            }
        }
    });
</script>
