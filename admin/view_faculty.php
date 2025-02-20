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
    $average_rating = round($avg_result['average_rating'] ?? 0, 2); // Rounded to 2 decimal places

    // Get the total number of reviews and the rating distribution
    $rating_query = $conn->query(
        "SELECT rate, COUNT(*) AS count
        FROM evaluation_answers
        JOIN evaluation_list ON evaluation_list.evaluation_id = evaluation_answers.evaluation_id
        WHERE evaluation_list.faculty_id = " . $_GET['id'] . "
        GROUP BY rate"
    );
    $rating_data = [];
    $total_reviews = 0;
    while ($row = $rating_query->fetch_assoc()) {
        $rating_data[$row['rate']] = $row['count'];
        $total_reviews += $row['count']; // Sum up total reviews
    }

    // Ensure all ratings from 1 to 5 are represented
    for ($i = 1; $i <= 5; $i++) {
        if (!isset($rating_data[$i])) {
            $rating_data[$i] = 0;
        }
    }

    // Calculate the percentage for each rating
    $rating_percentages = [];
    foreach ($rating_data as $rate => $count) {
        $rating_percentages[$rate] = $total_reviews > 0 ? round(($count / $total_reviews) * 100, 2) : 0;
    }

    // Determine the review status
    $status = 'Negative';
    if ($average_rating >= 4) {
        $status = 'Positive';
    } elseif ($average_rating >= 3) {
        $status = 'Average';
    }

    // Fetch the academic year from the database
    $academic_year_query = $conn->query("SELECT year FROM academic_list WHERE id = " . $_GET['id']);
    $academic_year = $academic_year_query->fetch_assoc()['year'] ?? 'N/A';

    // Fetch comments for sentiment analysis
// Fetch comments for sentiment analysis
    $comments_query = $conn->query(
        "SELECT evaluation_answers.comments
    FROM evaluation_answers
    JOIN evaluation_list ON evaluation_list.evaluation_id = evaluation_answers.evaluation_id
    WHERE evaluation_list.faculty_id = " . $_GET['id']
    );

    $comments = [];
    while ($row = $comments_query->fetch_assoc()) {
        $comments[] = $row['comments'];
    }

    // Stop words to be removed before analysis
    $stop_words = [
        'the',
        'is',
        'at',
        'which',
        'on',
        'and',
        'to',
        'for',
        'with',
        'a',
        'an',
        'of',
        'in',
        'he',
        'she',
        'He',
        'She',
        'His',
        'his'
    ];

    // Positive and negative phrases for context-aware sentiment analysis
    $positive_words = [
        'excellent teacher',
        'great teaching',
        'amazing teacher',
        'very good',
        'outstanding teacher',
        'best teaching experience',
        'highly recommended teacher',
        'superb teacher',
        'brilliant teaching',
        'remarkable teacher',
        'good teacher',
        'good teaching',
        'very nice teacher'
    ];

    $negative_words = [
        'bad teaching',
        'not good',
        'bad at',
        'very bad',
        'poor teacher',
        'bad teacher',
        'horrible teacher',
        'mediocre teacher',
        'awful teacher',
        'terrible teacher',
        'not good in teaching',
        'bad in teaching',
        'poor teaching',
        'disappointing teaching',
        'subpar teacher',
        'not good teacher'
    ];

    // Initialize counters for positive and negative sentiment
    $positive_count = 0;
    $negative_count = 0;

    // Function to handle both two-word and three-word phrases
    function handle_phrases($sentence, $positive_words, $negative_words, $stop_words)
    {
        // Convert sentence to lowercase for case-insensitive comparison
        $sentence_lower = strtolower($sentence);

        // Remove stop words
        $words = explode(' ', $sentence_lower);
        $filtered_words = array_diff($words, $stop_words);
        $sentence_lower = implode(' ', $filtered_words);

        // Check if "not good" appears anywhere in the sentence; prioritize negative sentiment
        if (strpos($sentence_lower, 'not good') !== false) {
            return ['negative', 'not good'];
        }

        // Check for negative phrases
        foreach ($negative_words as $phrase) {
            if (strpos($sentence_lower, strtolower($phrase)) !== false) {
                return ['negative', $phrase];
            }
        }

        // Check for positive phrases
        foreach ($positive_words as $phrase) {
            if (strpos($sentence_lower, strtolower($phrase)) !== false) {
                return ['positive', $phrase];
            }
        }

        return ['neutral', null];
    }

    // Analyze sentiment of each comment
    $unique_comments = array_unique($comments);
    foreach ($unique_comments as $comment) {
        list($sentiment, $phrase) = handle_phrases($comment, $positive_words, $negative_words, $stop_words);

        // // Log the detected sentiment and the matching phrase
        // echo "Comment: \"$comment\"<br>";
        // echo "Sentiment: $sentiment<br>";
        // echo "Matching Phrase: $phrase<br><br>";

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


    // Fetch the academic year from the database again (for possible duplicate call in original code)
    $academic_year_query = $conn->query("SELECT year FROM academic_list WHERE id = " . $_GET['id']);
    $academic_year = $academic_year_query->fetch_assoc()['year'] ?? 'N/A';
}
?>


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
                            <div class="col-md-6">
                                <dl>
                                    <dt>School Year</dt>
                                    <dd><?php echo $academic_year ?></dd>
                                </dl>
                            </div>
                            <!-- Label-Value Pair for Status -->
                            <div class="col-md-6">
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
                            <!-- Label-Value Pair for Total Reviews -->
                            <div class="col-md-6">
                                <dl>
                                    <dt>Total Questions</dt>
                                    <dd><?php echo $total_reviews ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <!-- Rating Chart -->
                    <div class="card-body">
                        <canvas id="ratingChart"></canvas>
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
                    // Initialize counts for unique positive and negative comments
                    $unique_positive_count = 0;
                    $unique_negative_count = 0;

                    foreach ($unique_comments as $comment) {
                        list($comment_sentiment, $matched_phrase) = handle_phrases($comment, $positive_words, $negative_words, $stop_words);

                        // Standardize sentiment values
                        $comment_sentiment = trim(strtolower($comment_sentiment));

                        if ($comment_sentiment === 'positive') {
                            $unique_positive_count++;
                        } elseif ($comment_sentiment === 'negative') {
                            $unique_negative_count++;
                        }
                    }

                    // Determine overall sentiment
                    $overall_sentiment = ($unique_positive_count > $unique_negative_count) ? 'Positive' :
                        (($unique_negative_count > $unique_positive_count) ? 'Negative' : 'Neutral');
                    ?>

                    <dl>
                        <dt>Positive Comments</dt>
                        <dd><?php echo $unique_positive_count; ?></dd>
                        <dt>Negative Comments</dt>
                        <dd><?php echo $unique_negative_count; ?></dd>
                        <dt>Overall Sentiment</dt>
                        <dd>
                            <span class="badge 
                        <?php echo ($overall_sentiment == 'Positive') ? 'bg-success' :
                            (($overall_sentiment == 'Negative') ? 'bg-danger' : 'bg-warning'); ?>">
                                <?php echo $overall_sentiment; ?>
                            </span>
                        </dd>
                    </dl>

                    <!-- Table to display unique comments and their sentiment -->
                    <table class="table table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>Comment</th>
                                <th>Sentiment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unique_comments as $comment): ?>
                                <?php
                                list($comment_sentiment, $matched_phrase) = handle_phrases($comment, $positive_words, $negative_words, $stop_words);

                                // Standardize sentiment values
                                $comment_sentiment = trim(strtolower($comment_sentiment));

                                // Set class based on sentiment
                                $badge_class = 'bg-warning'; // Default to yellow
                                if ($comment_sentiment == 'positive') {
                                    $badge_class = 'bg-success'; // Green
                                } elseif ($comment_sentiment == 'negative') {
                                    $badge_class = 'bg-danger'; // Red
                                }
                                ?>
                                <tr>
                                    <td><?php echo nl2br(htmlspecialchars($comment)); ?></td>
                                    <td>
                                        <span class="badge <?php echo $badge_class; ?> text-white">
                                            <?php echo ucfirst($comment_sentiment); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>



    </div>
</div>



</div>
</div>

<div class="modal-footer display p-0 m-0">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
</div>

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

<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    var ctx = document.getElementById('ratingChart').getContext('2d');
    var ratingData = <?php echo json_encode($rating_data); ?>;

    var ratingChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [1, 2, 3, 4, 5], // Ratings 1 to 5
            datasets: [{
                label: 'Number of Reviews',
                data: [ratingData[1], ratingData[2], ratingData[3], ratingData[4], ratingData[5]],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(153, 102, 255, 0.2)',
                    'rgba(255, 99, 132, 0.2)'
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 99, 132, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        afterLabel: function (context) {
                            var percentage = <?php echo json_encode($rating_percentages); ?>;
                            return percentage[context.raw] + '% of total reviews';
                        }
                    }
                }
            }
        }
    });
</script>
