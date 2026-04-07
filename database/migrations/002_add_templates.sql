-- Migration 002: Update templates — add white backgrounds, expand to 7
-- Run AFTER 001_initial_schema.sql
-- Date: 2026-04-07

-- Clear old templates and insert updated set
DELETE FROM templates;

INSERT INTO templates (id, name, config_json, sort_order) VALUES
    (1, 'Clean White', '{"primary":"#ffffff","secondary":"#1e293b","accent":"#3498db","font_heading":"Inter","font_body":"Inter","style":"light"}', 1),
    (2, 'Light Elegant', '{"primary":"#fafafa","secondary":"#1e293b","accent":"#8b5cf6","font_heading":"Playfair Display","font_body":"Source Sans Pro","style":"light"}', 2),
    (3, 'Corporate', '{"primary":"#1e3a5f","secondary":"#ffffff","accent":"#3498db","font_heading":"Inter","font_body":"Inter","style":"clean"}', 3),
    (4, 'Creative', '{"primary":"#ff6b6b","secondary":"#ffeaa7","accent":"#6c5ce7","font_heading":"Poppins","font_body":"Open Sans","style":"bold"}', 4),
    (5, 'Minimal', '{"primary":"#2d3436","secondary":"#ffffff","accent":"#00b894","font_heading":"Raleway","font_body":"Roboto","style":"minimal"}', 5),
    (6, 'Dark', '{"primary":"#0a0a0a","secondary":"#e0e0e0","accent":"#e94560","font_heading":"Raleway","font_body":"Roboto","style":"dark"}', 6),
    (7, 'Vibrant', '{"primary":"#667eea","secondary":"#ffffff","accent":"#f093fb","font_heading":"Montserrat","font_body":"Nunito","style":"gradient"}', 7);
