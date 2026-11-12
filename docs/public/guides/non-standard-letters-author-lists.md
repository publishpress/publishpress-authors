---
title: "Using Non-Standard Letters in Author Lists"
order: 59
doc_category: publishpress-authors
---

# Using Non-Standard Letters in Author Lists

The Authors Lists feature in [PublishPress Authors](https://publishpress.com/links/authors) uses the standard A-Z characters to sort authors. It is possible to use non-standard letters for these lists.

For example, in the Icelandic language you will see names such as this: Árni, Ívar, Ólafur, Úlfar, Ýmir, Ævar, Þorvaldur. To correctly sort these names alphabetically, you can map these letters to the standard A-Z characters. The code below will map these Icelandic characters. You can modify those for code your own language requirements.

```
add_filter('publishpress_authors_index_titles', function($titles) {  
    return [  
        'A' => 'A/Á',  
        'B' => 'B',   
        'C' => 'C',  
        'D' => 'D/Ð',  
        'E' => 'E/É',  
        'F' => 'F',  
        'G' => 'G',  
        'H' => 'H',  
        'I' => 'I/Í',  
        'J' => 'J',  
        'K' => 'K',  
        'L' => 'L',  
        'M' => 'M',  
        'N' => 'N',  
        'O' => 'O/Ó',  
        'P' => 'P',  
        'Q' => 'Q',  
        'R' => 'R',  
        'S' => 'S',  
        'T' => 'T/Þ',  
        'U' => 'U/Ú',  
        'V' => 'V',  
        'W' => 'W',  
        'X' => 'X',  
        'Y' => 'Y/Ý',  
        'Z' => 'Z'  
    ];  
});
```
