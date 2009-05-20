<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../../admin/template.xsl" />

  <xsl:template match="content[@name = 'tree']" mode="content">
    <div class="doclist">
      <h2>
        <xsl:value-of select="@title" />
      </h2>
      <xsl:if test="not(count(data/node))">
        <xsl:if test="@search = 'yes'">
          <xsl:call-template name="mcms_list_search" />
        </xsl:if>
        <p>Нет данных для отображения. Пересмотрите поисковый запрос или добавьте новый объект.</p>
      </xsl:if>
      <xsl:if test="count(data/node)">
        <xsl:if test="@search = 'yes'">
          <xsl:call-template name="mcms_list_search" />
        </xsl:if>
        <xsl:apply-templates select="massctl" mode="mcms_list" />

        <form id="nodeList" method="post" action="?q=nodeapi.rpc&amp;destination={/page/@back}">
          <fieldset>
            <input id="nodeListCommand" type="hidden" name="action" value="" />
          </fieldset>
          <ul class="nodes">
            <xsl:apply-templates select="data[../@preset = 'taxonomy']/node" mode="mcms_list" />
          </ul>
        </form>
        <xsl:apply-templates select="massctl" mode="mcms_list" />
      </xsl:if>
    </div>
  </xsl:template>

  <!-- вывод разделов -->
  <xsl:template match="node" mode="mcms_list">
    <li>
      <xsl:attribute name="class">
        <xsl:if test="position() mod 2">
          <xsl:text> odd</xsl:text>
        </xsl:if>
        <xsl:if test="@published">
          <xsl:text> published</xsl:text>
        </xsl:if>
        <xsl:if test="not(@published)">
          <xsl:text> unpublished</xsl:text>
        </xsl:if>
        <xsl:if test="position() = last()">
          <xsl:text> last</xsl:text>
        </xsl:if>
        <xsl:if test="children/node">
          <xsl:text> with-children</xsl:text>
        </xsl:if>
      </xsl:attribute>
      
      <xsl:if test="children/node">
        <span class="expand-collapse" />
      </xsl:if>
      
      <span class="container">
        <a title="Найти все документы из этого раздела" href="admin/content/list?search=tags%3A{@id}">
          <xsl:attribute name="class">
          <xsl:text>picker</xsl:text>
            <xsl:if test="not(@published)">
              <xsl:text> unpublished</xsl:text>
            </xsl:if>
          </xsl:attribute>
          <xsl:value-of select="@name" />
          <xsl:if test="not(@name)">
            <xsl:text>(без названия)</xsl:text>
          </xsl:if>
        </a>
        <span class="actions">
              <input type="checkbox" name="nodes[]" value="{@id}" />
              <a title="Добавить подраздел" class="icon tag-add" href="admin/create/tag/{@id}?destination={/page/@back}"></a>
              <xsl:if test="position() != 1"><a title="Поднять раздел" class="icon tag-up" href="?q=nodeapi.rpc&amp;action=raise&amp;node={@id}&amp;destination={/page/@back}"></a></xsl:if>
              <xsl:if test="position() != last()"><a title="Опустить раздел" class="icon tag-down" href="?q=nodeapi.rpc&amp;action=sink&amp;node={@id}&amp;destination={/page/@back}"></a></xsl:if>
              <a title="Изменить свойства раздела" class="icon tag-edit" href="admin/edit/{@id}?destination={/page/@back}"></a>
        </span>
      </span>
      <xsl:if test="children/node">
        <ul>
          <xsl:apply-templates select="children/node" mode="mcms_list" />
        </ul>
      </xsl:if>
    </li>
  </xsl:template>
</xsl:stylesheet>
