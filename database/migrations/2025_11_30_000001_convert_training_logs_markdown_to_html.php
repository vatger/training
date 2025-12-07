<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $logs = DB::table('training_logs')->get();
        
        foreach ($logs as $log) {
            $updates = [];
            
            $fields = [
                'theory_positives', 'theory_negatives',
                'phraseology_positives', 'phraseology_negatives',
                'coordination_positives', 'coordination_negatives',
                'tag_management_positives', 'tag_management_negatives',
                'situational_awareness_positives', 'situational_awareness_negatives',
                'problem_recognition_positives', 'problem_recognition_negatives',
                'traffic_planning_positives', 'traffic_planning_negatives',
                'reaction_positives', 'reaction_negatives',
                'separation_positives', 'separation_negatives',
                'efficiency_positives', 'efficiency_negatives',
                'ability_to_work_under_pressure_positives', 'ability_to_work_under_pressure_negatives',
                'motivation_positives', 'motivation_negatives',
                'internal_remarks', 'final_comment', 'special_procedures', 'airspace_restrictions'
            ];
            
            foreach ($fields as $field) {
                if (!empty($log->$field)) {
                    $converted = $this->convertMarkdownToHtml($log->$field);
                    if ($converted !== $log->$field) {
                        $updates[$field] = $converted;
                    }
                }
            }
            
            if (!empty($updates)) {
                DB::table('training_logs')
                    ->where('id', $log->id)
                    ->update($updates);
            }
        }
    }

    private function convertMarkdownToHtml(string $markdown): string
    {
        if (empty($markdown)) {
            return $markdown;
        }
        
        $html = $markdown;
        
        // Convert image syntax: ![](url) or ![alt](url) to <img src="url">
        $html = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2">', $html);
        
        // Convert bold: **text** or __text__ to <strong>text</strong>
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $html);
        
        // Convert italic: *text* or _text_ to <em>text</em>
        $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);
        $html = preg_replace('/_(.+?)_/', '<em>$1</em>', $html);
        
        // Convert headers: ## Header to <h2>Header</h2>
        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);
        
        // Convert unordered lists
        $html = $this->convertUnorderedLists($html);
        
        // Convert ordered lists
        $html = $this->convertOrderedLists($html);
        
        // Convert line breaks to paragraphs
        $html = $this->convertParagraphs($html);
        
        // Clean up any remaining markdown artifacts
        $html = trim($html);
        
        return $html;
    }
    
    private function convertUnorderedLists(string $text): string
    {
        $lines = explode("\n", $text);
        $result = [];
        $inList = false;
        $listItems = [];
        
        foreach ($lines as $line) {
            // Check if line starts with * or -
            if (preg_match('/^\s*[\*\-]\s+(.+)$/', $line, $matches)) {
                if (!$inList) {
                    $inList = true;
                    $listItems = [];
                }
                $listItems[] = '<li><p>' . trim($matches[1]) . '</p></li>';
            } else {
                // Not a list item
                if ($inList) {
                    // Close the list
                    $result[] = '<ul>' . implode('', $listItems) . '</ul>';
                    $inList = false;
                    $listItems = [];
                }
                $result[] = $line;
            }
        }
        
        // Close any open list
        if ($inList) {
            $result[] = '<ul>' . implode('', $listItems) . '</ul>';
        }
        
        return implode("\n", $result);
    }
    
    private function convertOrderedLists(string $text): string
    {
        $lines = explode("\n", $text);
        $result = [];
        $inList = false;
        $listItems = [];
        
        foreach ($lines as $line) {
            // Check if line starts with number.
            if (preg_match('/^\s*\d+\.\s+(.+)$/', $line, $matches)) {
                if (!$inList) {
                    $inList = true;
                    $listItems = [];
                }
                $listItems[] = '<li><p>' . trim($matches[1]) . '</p></li>';
            } else {
                // Not a list item
                if ($inList) {
                    // Close the list
                    $result[] = '<ol>' . implode('', $listItems) . '</ol>';
                    $inList = false;
                    $listItems = [];
                }
                $result[] = $line;
            }
        }
        
        // Close any open list
        if ($inList) {
            $result[] = '<ol>' . implode('', $listItems) . '</ol>';
        }
        
        return implode("\n", $result);
    }
    
    private function convertParagraphs(string $text): string
    {
        // Split by double newlines to identify paragraphs
        $blocks = preg_split('/\n\s*\n/', $text);
        $result = [];
        
        foreach ($blocks as $block) {
            $block = trim($block);
            if (empty($block)) {
                continue;
            }
            
            // Don't wrap if already wrapped in HTML tags
            if (preg_match('/^<(h[1-6]|ul|ol|img|p)/', $block)) {
                $result[] = $block;
            } else {
                // Wrap in paragraph tags
                $result[] = '<p>' . $block . '</p>';
            }
        }
        
        return implode('', $result);
    }

    public function down(): void
    {
        // This migration is not easily reversible
        // If you need to revert, restore from backup
    }
};