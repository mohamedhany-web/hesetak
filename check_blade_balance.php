<?php

function checkBladeBalance($file) {
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    
    $stack = [];
    $lineNumber = 0;
    $issues = [];
    
    $directives = [
        'if' => 'endif',
        'elseif' => null,
        'else' => null,
        'auth' => 'endauth',
        'guest' => 'endguest',
        'hasSection' => 'endhasSection',
        'hasany' => 'endhasany',
        'hassection' => 'endhassection',
        'foreach' => 'endforeach',
        'forelse' => 'endforelse',
        'for' => 'endfor',
        'while' => 'endwhile',
        'can' => 'endcan',
        'cannot' => 'endcannot',
        'canany' => 'endcanany',
        'once' => 'endonce',
        'unless' => 'endunless',
        'php' => 'endphp',
        'push' => 'endpush',
        'prepend' => 'endprepend',
        'section' => 'endsection',
        'slot' => 'endslot',
    ];
    
    foreach ($lines as $lineNum => $line) {
        $lineNumber = $lineNum + 1;
        
        // البحث عن جميع directives
        if (preg_match_all('/@(\w+)(\s*\([^)]*\))?/i', $line, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $directive = strtolower($match[1]);
                
                // تخطي التعليقات
                if (strpos($line, '//') !== false && strpos($line, '//') < strpos($line, '@')) {
                    continue;
                }
                
                // التحقق من directives البدء
                if (isset($directives[$directive]) && $directives[$directive] !== null) {
                    $stack[] = [
                        'directive' => $directive,
                        'line' => $lineNumber,
                        'closer' => $directives[$directive]
                    ];
                }
                // التحقق من directives الإغلاق
                else if (strpos($directive, 'end') === 0) {
                    $baseDirective = substr($directive, 3); // إزالة "end"
                    $found = false;
                    
                    // البحث في الـ stack من الأعلى للأسفل
                    for ($i = count($stack) - 1; $i >= 0; $i--) {
                        if ($stack[$i]['closer'] === $directive || 
                            $stack[$i]['directive'] === $baseDirective) {
                            array_splice($stack, $i, 1);
                            $found = true;
                            break;
                        }
                    }
                    
                    if (!$found) {
                        $issues[] = "Line $lineNumber: Unexpected @$directive without matching opening directive";
                    }
                }
                // التحقق من @elseif و @else
                else if ($directive === 'elseif' || $directive === 'else') {
                    // هذه لا تضيف إلى الـ stack ولكن يجب أن تكون داخل @if
                    if (empty($stack) || 
                        (end($stack)['directive'] !== 'if' && 
                         end($stack)['directive'] !== 'elseif' &&
                         end($stack)['directive'] !== 'unless')) {
                        $issues[] = "Line $lineNumber: @$directive without matching @if or @unless";
                    }
                }
            }
        }
    }
    
    // التحقق من directives غير مغلقة
    foreach ($stack as $item) {
        $issues[] = "Line {$item['line']}: Unclosed @{$item['directive']} - expecting @{$item['closer']}";
    }
    
    return $issues;
}

$files = [
    'resources/views/welcome.blade.php',
    'resources/views/layouts/app.blade.php',
    'resources/views/course-show.blade.php',
];

echo "Checking Blade files for unclosed directives...\n\n";

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "File not found: $file\n";
        continue;
    }
    
    echo "Checking: $file\n";
    $issues = checkBladeBalance($file);
    
    if (empty($issues)) {
        echo "  ✓ No issues found\n\n";
    } else {
        echo "  ✗ Found " . count($issues) . " issue(s):\n";
        foreach ($issues as $issue) {
            echo "    - $issue\n";
        }
        echo "\n";
    }
}

